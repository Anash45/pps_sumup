import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head, usePage } from "@inertiajs/react";
import { useState } from "react";
import axios from "axios";
import { Loader2 } from "lucide-react";

export default function Home() {
    const { flash, pdfs } = usePage().props;
    const [csvFile, setCsvFile] = useState(null);
    const [samplePdf, setSamplePdf] = useState("");
    const [loading, setLoading] = useState(false);
    const [pdfLinks, setPdfLinks] = useState(flash?.pdf_links || []);
    const [message, setMessage] = useState(flash?.message || "");
    const [errors, setErrors] = useState([]);

    console.log("pdfs: ", pdfs);

    const handleSubmit = async (e) => {
        e.preventDefault();
        if (!csvFile) return;

        const formData = new FormData();
        formData.append("csv_file", csvFile);
        formData.append("samplePdf", samplePdf);

        try {
            setLoading(true);
            setMessage("");
            setErrors([]);
            setPdfLinks([]);

            const response = await axios.post("/build-pdf", formData, {
                headers: {
                    "Content-Type": "multipart/form-data",
                    "X-CSRF-TOKEN": document
                        .querySelector('meta[name="csrf-token"]')
                        ?.getAttribute("content"),
                },
            });

            if (response.data.success) {
                setPdfLinks(response.data.pdf_links);
                setMessage(response.data.message);
            } else {
                setMessage(response.data.message || "Something went wrong!");
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
                setMessage("Failed to build PDF. Please try again.");
            }
        } finally {
            setLoading(false);
        }
    };

    return (
        <AuthenticatedLayout title="Home">
            <Head title="Home" />

            <div className="flex flex-col items-center justify-center min-h-[60vh] w-full px-4">
                <div className="w-full max-w-xl bg-white rounded-xl shadow-lg p-6 space-y-6">
                    <h1 className="text-2xl font-semibold text-gray-900 text-center">
                        Welcome to Home Page
                    </h1>

                    <form
                        onSubmit={handleSubmit}
                        className="flex flex-col space-y-4"
                    >
                        {/* samplePdf selection */}
                        <label className="font-medium text-gray-700">
                            Select Template File:
                        </label>
                        <select
                            name="samplePdf"
                            value={samplePdf}
                            onChange={(e) => setSamplePdf(e.target.value)}
                            required
                            className="border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        >
                            <option value="">Select file</option>
                            {pdfs.map((pdf, index) => (
                                <option key={index} value={pdf.id}>
                                    {pdf.title}
                                </option>
                            ))}
                        </select>

                        {/* CSV upload */}
                        <label className="font-medium text-gray-700">
                            Upload CSV:
                        </label>
                        <input
                            type="file"
                            name="csv_file"
                            accept=".csv"
                            required
                            onChange={(e) => setCsvFile(e.target.files[0])}
                            className="border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        />

                        {/* Validation errors */}
                        {errors.length > 0 && (
                            <div className="bg-red-50 border border-red-200 text-red-700 p-3 rounded">
                                <ul className="list-disc list-inside text-sm">
                                    {errors.map((err, i) => (
                                        <li key={i}>{err}</li>
                                    ))}
                                </ul>
                            </div>
                        )}

                        {/* Submit button */}
                        <button
                            type="submit"
                            disabled={loading}
                            className={`px-6 py-3 text-white font-medium rounded-lg shadow focus:outline-none transition flex items-center justify-center gap-2 ${
                                loading
                                    ? "bg-gray-400 cursor-not-allowed"
                                    : "bg-blue-600 hover:bg-blue-700"
                            }`}
                        >
                            {loading && (
                                <Loader2 className="h-5 w-5 animate-spin" />
                            )}
                            {loading ? "Building..." : "Build PDF"}
                        </button>
                    </form>

                    {/* Flash / message */}
                    {message && (
                        <div
                            className={`text-center text-lg font-medium ${
                                pdfLinks.length > 0
                                    ? "text-green-600"
                                    : "text-red-600"
                            }`}
                        >
                            {message}
                        </div>
                    )}

                    {/* PDF download buttons */}
                    {pdfLinks.length > 0 && (
                        <div className="flex flex-wrap gap-3 justify-center">
                            {pdfLinks.map((link, index) => {
                                const fileName = decodeURIComponent(
                                    link.split("/").pop()
                                );
                                return (
                                    <a
                                        key={index}
                                        href={`/download/${fileName}`}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        className="px-4 py-2 bg-green-600 text-white font-medium rounded-lg shadow hover:bg-green-700 transition"
                                    >
                                        Download {fileName}
                                    </a>
                                );
                            })}
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
