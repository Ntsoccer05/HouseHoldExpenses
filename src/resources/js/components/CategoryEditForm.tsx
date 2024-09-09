import * as React from "react";
import TableBody from "@mui/material/TableBody";
import TableCell from "@mui/material/TableCell";
import TableRow from "@mui/material/TableRow";
import Checkbox from "@mui/material/Checkbox";
import {
    FormControl,
    ListItemIcon,
    MenuItem,
    Select,
    SelectChangeEvent,
    TextField,
} from "@mui/material";
import { CategoryItem } from "../types";
import { useState } from "react";
import DynamicIcon from "./common/DynamicIcon";
import { expenseMuiIcons, incomeMuiIcons } from "../config/CategoryIcon";
import { useCategoryContext } from "../context/CategoryContext";

interface CategoryEditProps {
    edited: boolean;
    type: "income" | "expense";
    categories: CategoryItem[] | undefined;
    selected: readonly number[];
    swichedCategory: boolean;
    setSelected: React.Dispatch<React.SetStateAction<readonly number[]>>;
}

const CategoryEditForm = React.memo(
    ({
        edited,
        type,
        categories,
        selected,
        swichedCategory,
        setSelected,
    }: CategoryEditProps) => {
        const { editCategory } = useCategoryContext();

        const [initialized, setInitialized] = useState<boolean>(false); // 初期化済みかどうかのフラグ

        const handleClick = (event: React.MouseEvent<unknown>, id: number) => {
            const selectedIndex = selected.indexOf(id);
            let newSelected: readonly number[] = [];
            // debugger;
            if (selectedIndex === -1) {
                newSelected = newSelected.concat(selected, id);
            } else if (selectedIndex === 0) {
                newSelected = newSelected.concat(selected.slice(1));
            } else if (selectedIndex === selected.length - 1) {
                newSelected = newSelected.concat(selected.slice(0, -1));
            } else if (selectedIndex > 0) {
                newSelected = newSelected.concat(
                    selected.slice(0, selectedIndex),
                    selected.slice(selectedIndex + 1)
                );
            }
            setSelected(newSelected);
        };

        const isSelected = (id: number) => selected.indexOf(id) !== -1;

        const [contentValues, setContentValues] = useState<string[]>(
            categories?.map((category) => category.label || "") || []
        );
        const [iconValues, setIconValues] = useState<string[]>(
            categories?.map((category) => category.icon || "") || []
        );

        const setCategoryValues = (option: boolean) => {
            if (!categories) {
                return;
            } else if (categories.length === 0) {
                return;
            } else {
                if (categories[0].label !== "") {
                    if (option && initialized) {
                        return;
                    }
                    // カテゴリが取得できたときに初期値を設定
                    setContentValues(
                        (prevCategory) =>
                            (prevCategory = categories.map(
                                (category) => category.label || ""
                            ))
                    );
                    setIconValues(
                        (prevCategory) =>
                            (prevCategory = categories.map(
                                (category) => category.icon || ""
                            ))
                    );
                    option && setInitialized(() => true); // 初期化完了フラグを立てる
                }
            }
        };

        React.useEffect(() => {
            setCategoryValues(true);
        }, [categories, initialized]);

        React.useEffect(() => {
            setCategoryValues(false);
        }, [swichedCategory]);

        const handleCategoryChange =
            (index: number) => (event: React.ChangeEvent<HTMLInputElement>) => {
                if (categories && categories?.length > 0) {
                    const ids = extractIds(event.target.name);
                    if (ids) {
                        const newValues = [...contentValues];
                        newValues[index] = event.target.value;
                        setContentValues(newValues);
                        const id = ids && Number(ids.id);
                        const filtered_id = ids && Number(ids.filtered_id);
                        const fixed_category_id =
                            ids && Number(ids.fixed_category_id);
                        const content = event.target.value;
                        const tgtCategory = categories.filter((category) => {
                            return category.filtered_id === filtered_id;
                        });
                        const icon = tgtCategory[0].icon;
                        const argument = {
                            id,
                            content,
                            icon,
                            type,
                            fixed_category_id,
                        };
                        editCategory(argument);
                    }
                }
            };

        const handleIconChange =
            (index: number) => (event: SelectChangeEvent<string>) => {
                if (categories && categories?.length > 0) {
                    const ids = extractIds(event.target.name);
                    if (ids) {
                        const newValues = [...iconValues];
                        newValues[index] = event.target.value;
                        setIconValues(newValues);
                        const id = ids && Number(ids.id);
                        const filtered_id = ids && Number(ids.filtered_id);
                        const fixed_category_id =
                            ids && Number(ids.fixed_category_id);
                        const icon = event.target.value;
                        const tgtCategory = categories.filter((category) => {
                            return category.filtered_id === filtered_id;
                        });
                        const content = tgtCategory[0].label;
                        const argument = {
                            id,
                            icon,
                            content,
                            type,
                            fixed_category_id,
                        };
                        editCategory(argument);
                    }
                }
            };

        // 数字のみを取り出す関数
        const extractIds = (
            str: string
        ): {
            id: string;
            filtered_id: string;
            fixed_category_id: string;
        } | null => {
            // 正規表現を使ってidとfiltered_idを抽出
            const regex = /(\D+)(\d+)(\D+)(\d+)(\D+)(\d+)/;
            const matches = str.match(regex);
            if (matches) {
                const id = matches[2];
                const filtered_id = matches[4];
                const fixed_category_id = matches[6];
                return { id, filtered_id, fixed_category_id };
            }
            return null; // マッチしない場合はnullを返す
        };

        return (
            <TableBody>
                {categories?.map((category, index) => {
                    const isItemSelected = isSelected(
                        category.filtered_id || 0
                    );
                    const labelId = `enhanced-table-checkbox-${index}`;

                    return (
                        <TableRow
                            key={index}
                            hover={!edited} // 編集モードでは hover を無効化
                            onClick={(event) => {
                                if (!edited) {
                                    handleClick(
                                        event,
                                        category.filtered_id || 0
                                    );
                                }
                            }}
                            role="checkbox"
                            aria-checked={isItemSelected}
                            tabIndex={-1}
                            selected={isItemSelected}
                            sx={{
                                cursor: edited ? "default" : "pointer", // 編集モードではカーソルをデフォルトに
                                textAlign: "center",
                            }}
                        >
                            {!edited && (
                                <TableCell padding="checkbox">
                                    <Checkbox
                                        color="primary"
                                        checked={isItemSelected}
                                        inputProps={{
                                            "aria-labelledby": labelId,
                                        }}
                                    />
                                </TableCell>
                            )}
                            <TableCell
                                component="th"
                                id={labelId}
                                scope="row"
                                padding="none"
                                align="center"
                            >
                                {edited ? (
                                    <TextField
                                        required
                                        name={
                                            "content_" +
                                            String(category.id) +
                                            "filteredContent_" +
                                            String(category.filtered_id) +
                                            "fixed_category_id_" +
                                            String(
                                                category.fixed_category_id || 0
                                            )
                                        }
                                        value={contentValues[index]}
                                        onChange={handleCategoryChange(index)}
                                    />
                                ) : (
                                    category.label
                                )}
                            </TableCell>
                            <TableCell align="center">
                                {edited ? (
                                    <FormControl fullWidth>
                                        <Select
                                            value={iconValues[index]}
                                            name={
                                                "icon_" +
                                                String(category.id) +
                                                "filteredIcon_" +
                                                String(category.filtered_id) +
                                                "fixed_category_id_" +
                                                String(
                                                    category.fixed_category_id ||
                                                        0
                                                )
                                            }
                                            onChange={handleIconChange(index)}
                                        >
                                            {(type === "expense"
                                                ? expenseMuiIcons
                                                : incomeMuiIcons
                                            ).map((categoryIcon, index) => {
                                                return (
                                                    <MenuItem
                                                        key={index}
                                                        value={categoryIcon}
                                                    >
                                                        <ListItemIcon>
                                                            <DynamicIcon
                                                                iconName={
                                                                    categoryIcon
                                                                }
                                                                fontSize="medium"
                                                            />
                                                        </ListItemIcon>
                                                    </MenuItem>
                                                );
                                            })}
                                        </Select>
                                    </FormControl>
                                ) : (
                                    <DynamicIcon
                                        iconName={category.icon}
                                        fontSize="medium"
                                    />
                                )}
                            </TableCell>
                        </TableRow>
                    );
                })}
            </TableBody>
        );
    }
);
export default CategoryEditForm;
