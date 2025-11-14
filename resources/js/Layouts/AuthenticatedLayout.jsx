import ApplicationLogo from "@/Components/ApplicationLogo";
import { Link, usePage } from "@inertiajs/react";
import { Menu, X } from "lucide-react";
import React, { useState } from "react";

export default function AuthenticatedLayout({ title = "MyApp", children }) {
    const [mobileMenuOpen, setMobileMenuOpen] = useState(false);
    const { url } = usePage(); // current URL

    const navLinks = [
        { name: "Dashboard", href: "/dashboard" },
        { name: "Sample PDFs", href: "/sample-pdfs" },
        { name: "Profile", href: "/profile" },
        { name: "Logout", href: "/logout" },
    ];

    const renderLink = (link) => {
        const isActive = url === link.href;
        const baseClasses = "font-medium px-3 py-2 rounded transition-colors";
        const activeClasses = isActive
            ? "bg-blue-600 text-white"
            : "text-gray-700 hover:text-gray-900";

        return (
            <Link
                key={link.href}
                href={link.href}
                className={`${baseClasses} ${activeClasses}`}
                onClick={() => setMobileMenuOpen(false)}
            >
                {link.name}
            </Link>
        );
    };

    return (
        <div className="min-h-screen bg-gray-50 flex flex-col">
            {/* 🧭 Navbar */}
            <nav className="bg-white shadow-sm py-4">
                <div className="container mx-auto flex items-center justify-between px-4">
                    {/* Logo */}
                    <Link to="/">
                        <ApplicationLogo className="h-8" />
                    </Link>

                    {/* Links (desktop) */}
                    <div className="space-x-2 hidden md:flex">
                        {navLinks.map(renderLink)}
                    </div>

                    {/* Mobile menu button */}
                    <div className="md:hidden">
                        <button
                            type="button"
                            onClick={() => setMobileMenuOpen(!mobileMenuOpen)}
                            className="text-gray-700 hover:text-gray-900 focus:outline-none"
                        >
                            {mobileMenuOpen ? (
                                <X className="h-6 w-6" />
                            ) : (
                                <Menu className="h-6 w-6" />
                            )}
                        </button>
                    </div>
                </div>

                {/* Mobile menu (animated) */}
                <div
                    className={`md:hidden overflow-hidden transition-all duration-300 ${
                        mobileMenuOpen ? "max-h-96" : "max-h-0"
                    }`}
                >
                    <div className="flex flex-col px-4 pb-4 space-y-2 bg-white pt-4">
                        {navLinks.map(renderLink)}
                    </div>
                </div>
            </nav>

            {/* 📄 Page Content */}
            <main className="flex-1 p-6">{children}</main>
        </div>
    );
}
