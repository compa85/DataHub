import { useEffect } from "react";
import { useSelector, useDispatch } from "react-redux";
import { addRow } from "../redux/rowsSlice";
import { addField, resetFields } from "../redux/formSlice";
import { dbInsert } from "../database";
import {
    Button,
    Modal,
    ModalContent,
    ModalHeader,
    ModalBody,
    ModalFooter,
    Input,
    Spinner,
} from "@nextui-org/react";

function CustomFormModal({ table, isOpen, onOpenChange, showToast }) {
    // ======================================== REDUX =========================================
    const columns = useSelector((state) => state.columns.values);
    const loading = useSelector((state) => state.loading.values);
    const form = useSelector((state) => state.form.values);
    const dispatch = useDispatch();

    // aggiornare il form
    function handleInputChange(e) {
        const { value, name } = e.target;
        dispatch(addField({ fieldName: name, fieldValue: value }));
    }

    // =================================== CARICAMENTO INPUT ==================================
    useEffect(() => {
        columns.forEach((column) => {
            dispatch(addField({ fieldName: column.Field, fieldValue: "" }));
        });
    }, [columns]);

    // ====================================== INSERIMENTO =====================================
    function handleSubmit() {
        let object = {};

        for (const field in form) {
            if (form[field] !== "") {
                object[field] = form[field];
            }
        }

        dbInsert({ [table]: [object] }).then((response) => {
            console.log(response);
            showToast(response);

            object = {
                ...object,
                CodAttore: String(response.result[0]),
            };
            dispatch(addRow(object));
            dispatch(resetFields());
        });
    }

    // ======================================== PULISCI =======================================
    function handleReset() {
        dispatch(resetFields());
    }

    // ======================================== RETURN ========================================
    return (
        <Modal isOpen={isOpen} onOpenChange={onOpenChange}>
            <ModalContent>
                {(onClose) => (
                    <>
                        <ModalHeader className="flex flex-col gap-1">
                            Aggiungi
                        </ModalHeader>
                        <ModalBody>
                            {loading.header == true ? (
                                <Spinner label="Caricamento..." />
                            ) : (
                                columns.map((column) => (
                                    <Input
                                        isRequired
                                        key={column.Field}
                                        name={column.Field}
                                        placeholder={column.Field}
                                        variant="bordered"
                                        value={form[column.Field]}
                                        onChange={handleInputChange}
                                    />
                                ))
                            )}
                        </ModalBody>
                        <ModalFooter className="flex justify-center">
                            <Button color="primary" onPress={handleSubmit}>
                                Aggiungi
                            </Button>
                            <Button color="default" onPress={handleReset}>
                                Cancella
                            </Button>
                        </ModalFooter>
                    </>
                )}
            </ModalContent>
        </Modal>
    );
}

export default CustomFormModal;
