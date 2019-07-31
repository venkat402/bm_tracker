//defining module 
var app = angular.module("BulkMail", ['ngRoute']);

//defining contants 
app.constant('urls', {
    'baseUrl': window.location.origin+'/', //http://localhost/codeang.com/
    'apiUrl': window.location.origin+'/api/index.php/', //http://localhost/codeang.com/api/index.php/
    'someElseSetting': 'settingValue'
});


//defining routes 
app.config(function ($routeProvider) {
    $routeProvider
        .when('/', {
            templateUrl: 'frontend/home/main_home.html',
            controller: 'HomeController'
        })
        .when('/home/ci', {
            templateUrl: 'frontend/home/ci.html',
            controller: 'CiController'
        })
        .when('/home/search', {
            templateUrl: 'frontend/home/search_client.html',
            controller: 'SearchController'
        })
        .when('/home/backend', {
            templateUrl: 'frontend/home/backend.html',
            controller: 'BackendController'
        })
        .when('/home/usage_chart', {
            templateUrl: 'frontend/home/usage_chart.html',
            controller: 'UsageChartController'
        })
        .when('/home/dns/:getdomain', {
            templateUrl: 'frontend/home/dns.html',
            controller: 'DnsController'
        })
        .when('/home/show_domain/:getdomain', {
            templateUrl: 'frontend/home/show_domain.html',
            controller: 'ShowDomainController'
        })
        .when('/settings/manage_servers', {
            templateUrl: 'frontend/settings/manage_servers.html',
            controller: 'ManageServersController'
        })
        .when('/settings/tracker_note', {
            templateUrl: 'frontend/settings/tracker_note.html',
            controller: 'TrackerNoteController'
        })
        .when('/settings/domain_health', {
            templateUrl: 'frontend/settings/domain_health.html',
            controller: 'DomainHealthController'
        })
        .when('/settings/move_ceo', {
            templateUrl: 'frontend/settings/move_ceo.html',
            controller: 'MoveCeoController'
        })
        .when('/settings/tracker_unsubscribe', {
            templateUrl: 'frontend/settings/tracker_unsubscribe.html',
            controller: 'TrackerUnsubscribeController'
        })
        .when('/reportbug/bug_support', {
            templateUrl: 'frontend/reportbug/bug_support.html',
            controller: 'BugSupportController'
        })
        .when('/reportbug/bug_developer', {
            templateUrl: 'frontend/reportbug/bug_developer.html',
            controller: 'BugDeveloperController'
        })
        .when('/home/login', {
            templateUrl: 'frontend/home/login.html',
            controller: 'LoginController'
        })
        .when('/home/logout', {
            templateUrl: 'frontend/home/login.html',
            controller: 'LogoutController'
        })
        .otherwise({ redirectTo: '/' });
});



//code invisible controller 
app.controller('CiController', function ($scope, $http, urls) {
    $http.get(urls.apiUrl + 'home/ci').
        then(function (response) {
            $scope.home_data = response.data;
        });
});

//domain health controller
app.controller('DomainHealthController', function ($scope, $http, urls) {
    $http.get(urls.apiUrl + 'settings/domain_health').
        then(function (response) {
            $scope.settings_data = response.data;
        });
});

//backend controller 
app.controller('BackendController', function ($scope, $http, urls) {
    $http.get(urls.apiUrl + 'home/backend').
        then(function (response) {
            $scope.home_data = response.data;
        });
});

//TrackerUnsubscribeController
app.controller('TrackerUnsubscribeController', function ($scope, $http, urls) {
    $scope.user = {};
    console.log($scope.user);
    $scope.submitForm = function () {
        console.log("I've been pressed!");
        console.log(urls.apiUrl + 'settings/tracker_unsubscribe');
        //console.log($scope.user);
        console.log($scope.user);
        $http.post(urls.apiUrl + 'settings/tracker_unsubscribe', $scope.user).
            then(function (response) {
                console.log(response.data);
                $scope.settings_data = response.data;
            });
    };
}
);

//MoveCeoController
app.controller('MoveCeoController', function ($scope, $http, urls) {
    $http.get(urls.apiUrl + 'settings/move_ceo').
        then(function (response) {
            $scope.settings_data = response.data;
        });
    $scope.user = {};
    console.log($scope.user);
    $scope.submitForm = function () {
        console.log("I've been pressed!");
        console.log(urls.apiUrl + 'settings/moveceo');
        //console.log($scope.user);
        console.log($scope.user);
        $http.post(urls.apiUrl + 'settings/move_ceo', $scope.user).
            then(function (response) {
                console.log(response.data);
                $scope.settings_data = response.data;
            });
    };

    $scope.remove_ceo = function (id) {
        console.log(id);
        $http.post(urls.apiUrl + 'settings/delete_move_ceo', { id: id }).
            then(function (response) {
                console.log(response.data);
                $scope.settings_data = response.data;
            });

    }
}
);



//TrackerNoteController
app.controller('TrackerNoteController', function ($scope, $http, urls) {
    $http.get(urls.apiUrl + 'settings/tracker_note').
        then(function (response) {
            $scope.settings_data = response.data;
        });
    $scope.form = {};
    $scope.form2 = {};

    $scope.submitFormExpire = function () {
        $http.post(urls.apiUrl + 'settings/tracker_note_expire', $scope.form).
            then(function (response) {
                $scope.settings_data = response.data;
            });
    };
    $scope.submitFormData = function () {
        $http.post(urls.apiUrl + 'settings/tracker_note_data', $scope.form2).
            then(function (response) {
                $scope.settings_data = response.data;
            });
    };

    $scope.remove_note = function (id) {
        $http.post(urls.apiUrl + 'settings/delete_tracker_note', { id: id }).
            then(function (response) {
                console.log(response.data);
                $scope.settings_data = response.data;
            });

    }
}
);

//ManageServersController
app.controller('ManageServersController', function ($scope, $http, urls) {
    $scope.form = {};
    $scope.submitForm = function () {
        console.log($scope.form);
        $http.post(urls.apiUrl + 'settings/manage_servers', $scope.form).then(function (response) {
            console.log(response.data);
            $scope.form = {};
            $scope.settings_data = response.data;
        });
    };
});

//UsageChartController
app.controller('UsageChartController', function ($scope, $http, urls) {
    $http.get(urls.apiUrl + 'home/usage_chart').
        then(function (response) {
            $scope.home_data = response.data;
            //bar chart code start 
            $("#graph_bar7").length && Morris.Bar({
                element: "graph_bar7",
                data: response.data.graph_bar7,
                xkey: "domainName",
                ykeys: ["emails"],
                labels: ["Emails Usage"],
                barRatio: .4,
                barColors: ["#26B99A", "#34495E", "#ACADAC", "#3498DB"],
                xLabelAngle: 35,
                hideHover: "auto",
                resize: !0
            }),
                $("#graph_bar2").length && Morris.Bar({
                    element: "graph_bar2",
                    data: response.data.graph_bar2,
                    xkey: "domainName",
                    ykeys: ["emails"],
                    labels: ["Emails Blacklist"],
                    barRatio: .4,
                    barColors: ["#26B99A", "#34495E", "#ACADAC", "#3498DB"],
                    xLabelAngle: 35,
                    hideHover: "auto",
                    resize: !0
                }),
                $("#graph_bar3").length && Morris.Bar({
                    element: "graph_bar3",
                    data: response.data.graph_bar3,
                    xkey: "domainName",
                    ykeys: ["emails"],
                    labels: ["Emails Usage"],
                    barRatio: .4,
                    barColors: ["#26B99A", "#34495E", "#ACADAC", "#3498DB"],
                    xLabelAngle: 35,
                    hideHover: "auto",
                    resize: !0
                }),
                $("#graph_bar4").length && Morris.Bar({
                    element: "graph_bar4",
                    data: response.data.graph_bar4,
                    xkey: "domainName",
                    ykeys: ["emails"],
                    labels: ["Domain Blacklist Count"],
                    barRatio: .4,
                    barColors: ["#26B99A", "#34495E", "#ACADAC", "#3498DB"],
                    xLabelAngle: 35,
                    hideHover: "auto",
                    resize: !0
                }),
                $("#graph_bar6").length && Morris.Bar({
                    element: "graph_bar6",
                    data: response.data.graph_bar6,
                    xkey: "domainName",
                    ykeys: ["emails"],
                    labels: ["Total Clients"],
                    barRatio: .4,
                    barColors: ["#26B99A", "#34495E", "#ACADAC", "#3498DB"],
                    xLabelAngle: 35,
                    hideHover: "auto",
                    resize: !0
                }),
                $("#graph_bar8").length && Morris.Bar({
                    element: "graph_bar8",
                    data: response.data.graph_bar8,
                    xkey: "customer_email",
                    ykeys: ["emailUsage"],
                    labels: ["Emails Usage"],
                    barRatio: .4,
                    barColors: ["#26B99A", "#34495E", "#ACADAC", "#3498DB"],
                    xLabelAngle: 35,
                    hideHover: "auto",
                    resize: !0
                }),
                console.log(response.data.graph_bar8);
            $("#graph_bar5").length && Morris.Bar({
                element: "graph_bar5",
                data: response.data.graph_bar5,
                xkey: "domainName",
                ykeys: ["emails"],
                labels: ["Campaigns Usage"],
                barRatio: .4,
                barColors: ["#26B99A", "#34495E", "#ACADAC", "#3498DB"],
                xLabelAngle: 35,
                hideHover: "auto",
                resize: !0
            });
            //bar chart code end
        });
});


//HomeController
app.controller('HomeController', function ($scope, $http, urls) {
    $scope.siteUrl = urls.baseUrl;
    $http.get(urls.apiUrl + 'home/home').
        then(function (response) {
            console.log(response.data);
            if(response.data == "Session required"){
                window.location.href = urls.baseUrl+"#!/home/login";
            }
            $scope.home_data = response.data;
            $scope.redirectionProblem = response.data.requiredData.clientsFailRedirect;
            //bar chart code start 
            $("#graph_bar10").length && Morris.Bar({
                element: "graph_bar10",
                data: response.data.dataCharts.graph_bar10,
                xkey: "domainName",
                ykeys: ["emails"],
                labels: ["Emails Usage"],
                barRatio: .4,
                barColors: ["#26B99A", "#34495E", "#ACADAC", "#3498DB"],
                xLabelAngle: 35,
                hideHover: "auto",
                resize: !0
            }),
                //bar chart code start 
                $("#graph_bar11").length && Morris.Bar({
                    element: "graph_bar11",
                    data: response.data.dataCharts.graph_bar11,
                    xkey: "domainName",
                    ykeys: ["emails"],
                    labels: ["Total clients"],
                    barRatio: .4,
                    barColors: ["#26B99A", "#34495E", "#ACADAC", "#3498DB"],
                    xLabelAngle: 35,
                    hideHover: "auto",
                    resize: !0
                });
        });
});

//DnsController
app.controller('DnsController', function ($scope, $http, urls, $routeParams) {
    console.log($routeParams.getdomain);
    $http.get(urls.apiUrl + 'home/dns/' + $routeParams.getdomain).
        then(function (response) {
            console.log(response.data);
            $scope.home_data = response.data;
        });
});

//ShowDomainController
app.controller('ShowDomainController', function ($scope, $http, urls, $routeParams) {
    console.log($routeParams.getdomain);
    $http.get(urls.apiUrl + 'home/show_domain/' + $routeParams.getdomain).
        then(function (response) {
            console.log(response.data);
            $scope.home_data = response.data;
            $("#graph_bar10").length && Morris.Bar({
                element: "graph_bar10",
                data: JSON.parse(response.data.emailsUsage),
                xkey: "customer_email",
                ykeys: ["emailUsage"],
                labels: ["Emails Usage"],
                barRatio: .4,
                barColors: ["#26B99A", "#34495E", "#ACADAC", "#3498DB"],
                xLabelAngle: 35,
                hideHover: "auto",
                resize: !0
            });
        });
});


//SearchController
app.controller('SearchController', function ($scope, $http, urls) {
    $scope.Searchform = {};

    $http.get(urls.apiUrl + 'home/sidenav').
        then(function (response) {
            $scope.sidenav_data = response.data;
        });
    $scope.searchChange = {}
    $scope.searchChange = function (email) {
        $http.post(urls.apiUrl + 'home/search', $scope.Searchform).then(function (response) {
            $scope.searchings = response.data;
            console.log(response.data);
        });
    };
});


//BugSupportController
app.controller('BugSupportController', function ($scope, $sce, urls) {
    $scope.trustSrc = function (src) {
        return $sce.trustAsResourceUrl(src);
    }
    $scope.loginUrl = { src: urls.baseUrl + "api/vendors/bug/login.php?username=support" };
});


//BugDeveloperController
app.controller('BugDeveloperController', function ($scope, $sce, urls) {
    $scope.trustSrc = function (src) {
        return $sce.trustAsResourceUrl(src);
    }
    $scope.loginUrl = { src: urls.baseUrl + "api/vendors/bug/login.php?username=developer" };
});

//LoginController
app.controller('LoginController', function ($scope, $http, urls,$window,$location) {
    $scope.user = {};
    console.log($scope.user);
    $scope.submitForm = function () {
        console.log("I've been pressed!");
        console.log(urls.apiUrl + 'auth/login');
        console.log($scope.user);
        $http.post(urls.apiUrl + 'auth/login', $scope.user).
            then(function (response) {
                console.log(response.data);
                $scope.data_result = response.data;
                if(response.data.trim() == "Login success"){
                    $window.location.href = urls.baseUrl;
                }
            });
    };
}
);
//LogoutController
app.controller('LogoutController', function ($scope, $http, urls,$window,$location) {
        $http.get(urls.apiUrl + 'auth/logout').
            then(function (response) {
                console.log(response.data);
                if(response.data == 'Loged out successfully'){
                    $scope.data_result = response.data;
                }
                $window.location.href = urls.baseUrl+'#!/home/login';
                // $scope.data_result = "Loged out successfully";
            });
        }
);