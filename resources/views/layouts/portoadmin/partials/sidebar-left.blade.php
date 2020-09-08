<aside id="sidebar-left" class="sidebar-left">

    <div class="sidebar-header">
        <div class="sidebar-title">
            Navigation
        </div>
        <div class="sidebar-toggle hidden-xs" data-toggle-class="sidebar-left-collapsed" data-target="html" data-fire-event="sidebar-left-toggle">
            <i class="fa fa-bars" aria-label="Toggle sidebar"></i>
        </div>
    </div>

    <div class="nano">
        <div class="nano-content">
            <nav id="menu" class="nav-main" role="navigation">

                <ul class="nav nav-main">
                    <li>
                        <a class="nav-link" href="layouts-default.html">
                            <i class="fa fa-home" aria-hidden="true"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li class="nav-parent nav-expanded nav-active">
                        <a class="nav-link" href="#">
                            <i class="fa fa-columns" aria-hidden="true"></i>
                            <span>Menu 1</span>
                        </a>
                        <ul class="nav nav-children">
                            <li>
                                <a class="nav-link" href="index.html">
                                    Submenu
                                </a>
                            </li>
                            <li>
                                <a class="nav-link" href="layouts-default.html">
                                    Submenu 2
                                </a>
                            </li>
                            <li class="nav-parent">
                                <a>
                                    Submenu 3
                                </a>
                                <ul class="nav nav-children">
                                    <li>
                                        <a class="nav-link" href="layouts-boxed.html">
                                            Item 1
                                        </a>
                                    </li>
                                    <li>
                                        <a class="nav-link" href="layouts-boxed-fixed-header.html">
                                            Item 2
                                        </a>
                                    </li>
                                </ul>
                            </li>
                            <li>
                                <a class="nav-link" href="#">
                                    Submenu 4
                                </a>
                            </li>
                        </ul>
                    </li>
                    <li>
                        <a class="nav-link" href="#">
                            <i class="fa fa-external-link" aria-hidden="true"></i>
                            <span>Front-End <em class="not-included">(Not Included)</em></span>
                        </a>
                    </li>
                    <li class="nav-parent">
                        <a class="nav-link" href="#">
                            <i class="fa fa-align-left" aria-hidden="true"></i>
                            <span>Menu Levels</span>
                        </a>
                        <ul class="nav nav-children">
                            <li>
                                <a>
                                    First Level
                                </a>
                            </li>
                            <li class="nav-parent">
                                <a class="nav-link" href="#">
                                    Second Level
                                </a>
                                <ul class="nav nav-children">
                                    <li>
                                        <a>
                                            Second Level Link #1
                                        </a>
                                    </li>
                                    <li>
                                        <a>
                                            Second Level Link #2
                                        </a>
                                    </li>
                                    <li class="nav-parent">
                                        <a class="nav-link" href="#">
                                            Third Level
                                        </a>
                                        <ul class="nav nav-children">
                                            <li>
                                                <a>
                                                    Third Level Link #1
                                                </a>
                                            </li>
                                            <li>
                                                <a>
                                                    Third Level Link #2
                                                </a>
                                            </li>
                                        </ul>
                                    </li>
                                </ul>
                            </li>
                        </ul>
                    </li>

                </ul>
            </nav>

            <hr class="separator" />

            <div class="sidebar-widget widget-tasks">
                <div class="widget-header">
                    <h6>Projects</h6>
                    <div class="widget-toggle">+</div>
                </div>
                <div class="widget-content">
                    <ul class="list-unstyled m-0">
                        <li><a href="#">Porto HTML5 Template</a></li>
                        <li><a href="#">Tucson Template</a></li>
                        <li><a href="#">Porto Admin</a></li>
                    </ul>
                </div>
            </div>

            <hr class="separator" />

            <div class="sidebar-widget widget-stats">
                <div class="widget-header">
                    <h6>Company Stats</h6>
                    <div class="widget-toggle">+</div>
                </div>
                <div class="widget-content">
                    <ul>
                        <li>
                            <span class="stats-title">Stat 1</span>
                            <span class="stats-complete">85%</span>
                            <div class="progress">
                                <div class="progress-bar progress-bar-primary progress-without-number" role="progressbar" aria-valuenow="85" aria-valuemin="0" aria-valuemax="100" style="width: 85%;">
                                    <span class="sr-only">85% Complete</span>
                                </div>
                            </div>
                        </li>
                        <li>
                            <span class="stats-title">Stat 2</span>
                            <span class="stats-complete">70%</span>
                            <div class="progress">
                                <div class="progress-bar progress-bar-primary progress-without-number" role="progressbar" aria-valuenow="70" aria-valuemin="0" aria-valuemax="100" style="width: 70%;">
                                    <span class="sr-only">70% Complete</span>
                                </div>
                            </div>
                        </li>
                        <li>
                            <span class="stats-title">Stat 3</span>
                            <span class="stats-complete">2%</span>
                            <div class="progress">
                                <div class="progress-bar progress-bar-primary progress-without-number" role="progressbar" aria-valuenow="2" aria-valuemin="0" aria-valuemax="100" style="width: 2%;">
                                    <span class="sr-only">2% Complete</span>
                                </div>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <script>
            // Maintain Scroll Position
            if (typeof localStorage !== 'undefined') {
                if (localStorage.getItem('sidebar-left-position') !== null) {
                    var initialPosition = localStorage.getItem('sidebar-left-position'),
                        sidebarLeft = document.querySelector('#sidebar-left .nano-content');
                    
                    sidebarLeft.scrollTop = initialPosition;
                }
            }
        </script>
        

    </div>

</aside>