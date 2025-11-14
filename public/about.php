<?php 
$pageTitle = "About Us";
include '../includes/header.php'; 
?>

<div class="w-full max-w-3xl mx-auto bg-white rounded-lg shadow-xl p-8">
    <h1 class="text-3xl font-bold text-center mb-6 text-gray-800 underline decoration-blue-500">
        About TechNews
    </h1>
    
    <div class="space-y-6 text-gray-700 leading-relaxed text-lg">
        <p>
            Welcome to <span class="font-semibold text-blue-600">TechNews</span>, where cutting-edge technology meets insightful analysis. 
            Born from a passion for innovation, we're your trusted source for the latest in AI, cybersecurity, 
            and digital transformation.
        </p>

        <div class="bg-blue-50 p-4 rounded-lg border-l-4 border-blue-500">
            <h2 class="font-bold text-xl mb-2 text-gray-800">Our Mission</h2>
            <p>
                To demystify complex tech concepts and deliver actionable insights that empower both tech 
                enthusiasts and professionals to navigate the digital landscape with confidence.
            </p>
        </div>

        <h2 class="font-bold text-xl mt-8 text-gray-800">Why We Stand Out</h2>
        <ul class="list-disc pl-5 space-y-2">
            <li><span class="font-medium">Expert-Verified Content:</span> Our team includes industry veterans and subject matter experts</li>
            <li><span class="font-medium">Timely Analysis:</span> We track emerging trends before they go mainstream</li>
            <li><span class="font-medium">Practical Focus:</span> Real-world applications over theoretical discussions</li>
        </ul>

        <div class="flex flex-col sm:flex-row gap-4 mt-6">
            <div class="flex-1 bg-gray-50 p-4 rounded-lg">
                <h3 class="font-semibold mb-2 flex items-center">
                    <i class="fas fa-bullseye mr-2 text-blue-500"></i> Our Vision
                </h3>
                <p>A world where technology bridges gaps rather than creating them, and where knowledge is accessible to all.</p>
            </div>
            <div class="flex-1 bg-gray-50 p-4 rounded-lg">
                <h3 class="font-semibold mb-2 flex items-center">
                    <i class="fas fa-users mr-2 text-blue-500"></i> Our Community
                </h3>
                <p>Join 50,000+ monthly readers who trust us for unbiased tech analysis and breakthrough reporting.</p>
            </div>
        </div>

        <p class="pt-4 text-center italic">
            "Technology alone is not enough. It's technology married with liberal arts, married with the humanities, 
            that yields the results that make our hearts sing." <br>
            <span class="font-medium">— Steve Jobs</span>
        </p>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

