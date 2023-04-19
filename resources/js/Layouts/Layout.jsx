import Navbar from "@/Components/Navbar";

export default function Layout({ children }) {
    return (
        <>
            <div className="relative sm:fle sm:justify-center sm:items-center bg-dots-darker text-blueNavy bg-center bg-blue-50 dark:bg-dots-lighter dark:bg-gray-900 dark:text-gray-100 min-h-screen transition-all duration-300 ease-in-out">
                <div className="max-w-7xl mx-auto">
                    <Navbar />
                    {children}
                </div>
            </div>
        </>
    );
}
