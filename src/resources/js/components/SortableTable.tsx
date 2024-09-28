import * as React from "react";
import TableCell from "@mui/material/TableCell";
import TableRow from "@mui/material/TableRow";
import Checkbox from "@mui/material/Checkbox";
import { SelectChangeEvent } from "@mui/material";
import { CategoryItem } from "../types";
import DynamicIcon from "./common/DynamicIcon";
import { useSortable } from "@dnd-kit/sortable";
import { CSS } from "@dnd-kit/utilities";
// SortableTableRow component
interface SortableTableRowProps {
    category: CategoryItem;
    edited: boolean;
    handleClick: (event: React.MouseEvent<unknown>, id: number) => void;
    isSelected: boolean;
}

const SortableTableRow = ({
    category,
    edited,
    handleClick,
    isSelected,
}: SortableTableRowProps) => {
    const { attributes, listeners, setNodeRef, transform, transition } =
        useSortable({ id: category.filtered_id });

    const style = {
        transform: CSS.Transform.toString(transform),
        transition,
    };

    return (
        <TableRow
            ref={setNodeRef}
            style={style} // ドラッグ用のstyleを適用
            {...attributes} // ドラッグ操作に必要なpropsを適用
            {...listeners} // ドラッグイベントリスナーを適用
            hover={!edited}
            onClick={(event) => {
                if (!edited) {
                    handleClick(event, category.filtered_id || 0);
                }
            }}
            role="checkbox"
            aria-checked={isSelected}
            tabIndex={-1}
            selected={isSelected}
            sx={{
                cursor: edited ? "default" : "pointer",
                textAlign: "center",
            }}
        >
            <TableCell padding="checkbox">
                <Checkbox color="primary" checked={isSelected} />
            </TableCell>
            <TableCell component="th" scope="row" padding="none" align="center">
                {category.label}
            </TableCell>
            <TableCell align="center">
                <DynamicIcon iconName={category.icon || ""} />
            </TableCell>
        </TableRow>
    );
};
export default SortableTableRow;
