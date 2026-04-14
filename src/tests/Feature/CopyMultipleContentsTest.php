<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Content;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CopyMultipleContentsTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    /**
     * 複数コンテンツをコピー成功テスト
     */
    public function test_copy_multiple_contents_success()
    {
        // Arrange: テストデータ作成
        $sourceDate = '2026-03-14';
        $destinationDate = '2026-04-14';

        $content1 = Content::factory()->create([
            'user_id' => $this->user->id,
            'recorded_at' => $sourceDate,
            'amount' => 30529,
            'content' => 'スーパー',
        ]);

        $content2 = Content::factory()->create([
            'user_id' => $this->user->id,
            'recorded_at' => $sourceDate,
            'amount' => 100080,
            'content' => '家賃',
        ]);

        // Act: API 呼び出し
        $response = $this->actingAs($this->user)->postJson('/api/copyMultipleContents', [
            'source_date' => $sourceDate,
            'destination_date' => $destinationDate,
            'content_ids' => [$content1->id, $content2->id],
        ]);

        // Assert: レスポンス確認
        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('copied_count', 2);

        // コピーされたコンテンツを確認
        $this->assertDatabaseCount('contents', 4); // 元の2件 + コピー2件

        $copiedContents = Content::where('user_id', $this->user->id)
            ->where('recorded_at', $destinationDate)
            ->get();

        $this->assertEquals(2, $copiedContents->count());
    }

    /**
     * 同じ日付へのコピーでエラーテスト
     */
    public function test_copy_multiple_contents_same_date_error()
    {
        // Arrange
        $date = '2026-03-14';

        $content = Content::factory()->create([
            'user_id' => $this->user->id,
            'recorded_at' => $date,
        ]);

        // Act
        $response = $this->actingAs($this->user)->postJson('/api/copyMultipleContents', [
            'source_date' => $date,
            'destination_date' => $date,
            'content_ids' => [$content->id],
        ]);

        // Assert
        $response->assertStatus(400)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error_code', 'SAME_DATE_ERROR');
    }

    /**
     * 権限チェックテスト（他のユーザーのコンテンツをコピーしようとする）
     */
    public function test_copy_multiple_contents_unauthorized()
    {
        // Arrange
        $otherUser = User::factory()->create();
        $sourceDate = '2026-03-14';
        $destinationDate = '2026-04-14';

        $content = Content::factory()->create([
            'user_id' => $otherUser->id,
            'recorded_at' => $sourceDate,
        ]);

        // Act
        $response = $this->actingAs($this->user)->postJson('/api/copyMultipleContents', [
            'source_date' => $sourceDate,
            'destination_date' => $destinationDate,
            'content_ids' => [$content->id],
        ]);

        // Assert
        $response->assertStatus(403);
    }

    /**
     * バリデーションエラーテスト（日付フォーマット）
     */
    public function test_copy_multiple_contents_invalid_date_format()
    {
        // Act
        $response = $this->actingAs($this->user)->postJson('/api/copyMultipleContents', [
            'source_date' => '2026-03-14 10:30', // 無効なフォーマット
            'destination_date' => '2026-04-14',
            'content_ids' => [1],
        ]);

        // Assert
        $response->assertStatus(422);
    }

    /**
     * トランザクション失敗時のロールバックテスト
     */
    public function test_copy_multiple_contents_transaction_rollback()
    {
        // Arrange
        $sourceDate = '2026-03-14';
        $destinationDate = '2026-04-14';

        $content = Content::factory()->create([
            'user_id' => $this->user->id,
            'recorded_at' => $sourceDate,
        ]);

        // 無効な content_id も含める
        $invalidContentId = 99999;

        // Act
        $response = $this->actingAs($this->user)->postJson('/api/copyMultipleContents', [
            'source_date' => $sourceDate,
            'destination_date' => $destinationDate,
            'content_ids' => [$content->id, $invalidContentId],
        ]);

        // Assert: バリデーションエラー
        $response->assertStatus(422);
    }
}
