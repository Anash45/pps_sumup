import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head, usePage } from "@inertiajs/react";
import { useState } from "react";
import axios from "axios";
import { Loader2, Trash2 } from "lucide-react"; // optional spinner icon
import { toast } from "react-toastify";

export default function Index() {
    const { flash, pdfs: initialPdfs } = usePage().props;
    const [title, setTitle] = useState("");
    const [file, setFile] = useState(null);
    const [pdfs, setPdfs] = useState(initialPdfs || []);
    const [loading, setLoading] = useState(false);
    const [errors, setErrors] = useState([]);
    const [message, setMessage] = useState(flash?.success || "");

    const handleSubmit = async (e) => {
        e.preventDefault();
        setLoading(true);
        setErrors([]);
        setMessage("");

        if (!title || !file) {
            setErrors(["Title and PDF file are required."]);
            setLoading(false);
            return;
        }

        const formData = new FormData();
        formData.append("title", title);
        formData.append("pdf_file", file);

        try {
            const response = await axios.post("/sample-pdfs", formData, {
                headers: {
                    "Content-Type": "multipart/form-data",
                    "X-CSRF-TOKEN": document
                        .querySelector('meta[name="csrf-token"]')
                        ?.getAttribute("content"),
                },
            });

            if (response.data.success) {
                setMessage(response.data.message);
                setPdfs((prev) => [...prev, response.data.pdf]);
                setTitle("");
                setFile(null);
            } else {
                setMessage(response.data.message || "Upload failed.");
                if (response.data.errors) {
                    setErrors(Object.values(response.data.errors).flat());
                }
            }
        } catch (err) {
            console.error(err);
            if (err.response?.status === 422) {
                const validationErrors = Object.values(
                    err.response.data.errors
                ).flat();
                setErrors(validationErrors);
                setMessage("Validation failed.");
            } else {
                setMessage("Failed to upload PDF. Please try again.");
            }
        } finally {
            setLoading(false);
        }
    };

    return (
        <AuthenticatedLayout title="Sample PDFs">
            <Head title="Sample PDFs" />

            <div className="flex flex-col items-center w-full px-4 py-6">
                <div className="w-full max-w-xl bg-white rounded-xl shadow-lg p-6 space-y-6">
                    <h1 className="text-2xl font-semibold text-gray-900 text-center">
                        Upload Sample PDF
                    </h1>

                    {/* Success / message */}
                    {message && (
                        <div
                            className={`p-3 rounded ${
                                errors.length > 0
                                    ? "bg-red-100 text-red-700"
                                    : "bg-green-100 text-green-700"
                            }`}
                        >
                            {message}
                        </div>
                    )}

                    {/* Validation errors */}
                    {errors.length > 0 && (
                        <div className="bg-red-100 text-red-700 p-3 rounded">
                            <ul className="list-disc list-inside text-sm">
                                {errors.map((err, i) => (
                                    <li key={i}>{err}</li>
                                ))}
                            </ul>
                        </div>
                    )}

                    <form
                        onSubmit={handleSubmit}
                        className="flex flex-col space-y-4"
                    >
                        <label className="font-medium text-gray-700">
                            Title:
                        </label>
                        <input
                            type="text"
                            name="title"
                            value={title}
                            onChange={(e) => setTitle(e.target.value)}
                            required
                            className="border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        />

                        <label className="font-medium text-gray-700">
                            Select PDF:
                        </label>
                        <input
                            type="file"
                            name="pdf_file"
                            accept="application/pdf"
                            onChange={(e) => setFile(e.target.files[0])}
                            required
                            className="border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        />

                        <button
                            type="submit"
                            disabled={loading}
                            className={`px-6 py-3 text-white font-medium rounded-lg shadow bg-blue-600 hover:bg-blue-700 transition flex items-center justify-center gap-2 ${
                                loading ? "bg-gray-400 cursor-not-allowed" : ""
                            }`}
                        >
                            {loading && (
                                <Loader2 className="h-5 w-5 animate-spin" />
                            )}
                            {loading ? "Uploading..." : "Upload PDF"}
                        </button>
                    </form>

                    {/* List of uploaded PDFs */}
                    {pdfs.length > 0 && (
                        <div className="mt-6">
                            <h2 className="text-lg font-semibold mb-2">
                                Uploaded PDFs:
                            </h2>
                            <ul className="space-y-2">
                                {pdfs.map((pdf) => (
                                    <li
                                        key={pdf.id}
                                        className="border p-2 rounded flex justify-between items-center"
                                    >
                                        <span>{pdf.title}</span>
                                        <div className="flex items-center gap-2">
                                            <span className="text-gray-500 text-sm">
                                                {pdf.path}
                                            </span>
                                            <button
                                                onClick={async () => {
                                                    if (
                                                        !confirm(
                                                            `Delete "${pdf.title}"?`
                                                        )
                                                    )
                                                        return;

                                                    try {
                                                        const token = document
                                                            .querySelector(
                                                                'meta[name="csrf-token"]'
                                                            )
                                                            .getAttribute(
                                                                "content"
                                                            );

                                                        const response =
                                                            await fetch(
                                                                `/sample-pdfs/${pdf.id}`,
                                                                {
                                                                    method: "DELETE",
                                                                    headers: {
                                                                        "X-CSRF-TOKEN":
                                                                            token,
                                                                        Accept: "application/json",
                                                                    },
                                                                }
                                                            );

                                                        const data =
                                                            await response.json();

                                                        if (data.success) {
                                                            toast.success(
                                                                data.message
                                                            ); // ✅ show success toast
                                                            setPdfs((prev) =>
                                                                prev.filter(
                                                                    (p) =>
                                                                        p.id !==
                                                                        pdf.id
                                                                )
                                                            );
                                                        } else {
                                                            toast.error(
                                                                data.message ||
                                                                    "Failed to delete."
                                                            ); // ❌ error toast
                                                        }
                                                    } catch (err) {
                                                        console.error(err);
                                                        toast.error(
                                                            "Error deleting PDF."
                                                        ); // ❌ error toast
                                                    }
                                                }}
                                                className="px-2 py-1 text-sm bg-red-600 text-white rounded hover:bg-red-700 transition"
                                            >
                                                <Trash2 className="h-4 w-4" />
                                            </button>
                                        </div>
                                    </li>
                                ))}
                            </ul>
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
