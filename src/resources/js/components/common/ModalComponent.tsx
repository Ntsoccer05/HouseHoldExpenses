import { Box, Button, Typography } from "@mui/material";
import Modal from "@mui/material/Modal";
interface ModalComponentProps {
    mainMessage: string;
    contentMessage: string;
    showModal: boolean;
    modalOption?: number;
    handleCloseModal: () => void;
    handleFunc: () => void;
}
function ModalComponent({
    mainMessage,
    contentMessage,
    showModal,
    modalOption,
    handleCloseModal,
    handleFunc,
}: ModalComponentProps) {
    const style = {
        position: "absolute" as "absolute",
        top: "50%",
        left: "50%",
        transform: "translate(-50%, -50%)",
        width: 400,
        bgcolor: "background.paper",
        border: "2px solid #000",
        boxShadow: 24,
        p: 4,
    };

    return (
        <Modal open={showModal} onClose={handleCloseModal}>
            <Box sx={style}>
                <Typography id="modal-modal-title" variant="h6" component="h2">
                    {mainMessage}
                </Typography>
                <Typography id="modal-modal-description" sx={{ mt: 2 }}>
                    {contentMessage}
                </Typography>
                <Box mt={4} display="flex" justifyContent="center">
                    {modalOption ? (
                        <>
                            <Button
                                variant="contained"
                                color="primary"
                                onClick={handleFunc}
                            >
                                はい
                            </Button>
                            <Button
                                variant="contained"
                                color="primary"
                                onClick={handleCloseModal}
                            >
                                いいえ
                            </Button>
                        </>
                    ) : (
                        <Button
                            variant="contained"
                            color="primary"
                            onClick={handleCloseModal}
                        >
                            閉じる
                        </Button>
                    )}
                    <Button
                        variant="contained"
                        color="primary"
                        onClick={handleCloseModal}
                    >
                        閉じる
                    </Button>
                </Box>
            </Box>
        </Modal>
    );
}

export default ModalComponent;
