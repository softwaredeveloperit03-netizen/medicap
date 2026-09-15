import { Component, OnInit, OnDestroy, HostListener } from '@angular/core';
import { Subscription } from 'rxjs';
declare let alertify: any;
import { DataAccessService } from '../data-access.service';
import {
  Router,
  NavigationEnd,
  NavigationError,
} from '@angular/router';
import { ConnectivityService, ConnectivityStatus } from '../connectivity.service';
import { LanguageService } from '../shared/language.service';
import { TranslateService } from '@ngx-translate/core';

export type NotificationActionType = 'Checking' | 'Approval' | 'Review';
export type NotificationFilter = 'All' | NotificationActionType;

export interface PendingDocumentNotification {
  id: string;
  documentNo: string;
  title: string;
  module: string;
  actionType: NotificationActionType;
  department: string;
  requestedBy: string;
  pendingSince: string;
  priority: 'High' | 'Medium' | 'Low';
  route: string;
}

@Component({
  selector: 'app-navbar',
  templateUrl: './navbar.component.html',
  styleUrls: ['./navbar.component.css']
})
export class NavbarComponent implements OnInit, OnDestroy {
  username = '';
  userId = '';
  plant_logo = 'assets/medicap-logo.png';
  logo_path = 'assets/medicap-logo.png';
  myDate: Date;
  plant_type: string;
  plant_name: string;
  salary: any;
  type: string;
  username1: string;
  plant_type2: string;
  plant_name1: string;

  connectivityStatus: ConnectivityStatus = 'connected';
  connectivityLabel = 'Connected';
  private connectivitySub: Subscription;
  private routerSub: Subscription;
  private clockTimer: ReturnType<typeof setInterval> | null = null;
  languages = this.languageService.supportedLanguages;
  selectedLanguage = 'en';

  /** Pending document notifications (demo seed + department filter) */
  userDepartment = '';
  showNotificationsModal = false;
  notificationFilter: NotificationFilter = 'All';
  pendingNotifications: PendingDocumentNotification[] = [];

  constructor(
    public service: DataAccessService,
    private router: Router,
    private connectivity: ConnectivityService,
    public languageService: LanguageService,
    private translate: TranslateService
  ) {

    this.type = localStorage.getItem('type');
    this.username1 = localStorage.getItem('username');
    this.plant_type2 = localStorage.getItem('plant_type');
    this.plant_name1 = localStorage.getItem('plant_name');
    this.myDate = new Date();

    // Was 1ms â€” that forced ~1000 Angular change-detection cycles/sec and froze heavy micro pages
    this.clockTimer = setInterval(() => {
      this.myDate = new Date();
      if (!this.plant_name || !this.plant_logo) {
        this.loadPlantInfo();
      }
    }, 1000);




    const callApi = () => {
       console.log('Running the API call at 5:20pm');

       this.service.get('hr/employee.php?type=update_salary').subscribe(response => {
           this.salary = response;
           });
    };
    
    // Function to calculate the time until 5:20 pm and run the function
    const runAtSpecificTime = (targetTime, task) => {
      const currentTime = new Date();
      const targetTimeToday = new Date(
        currentTime.getFullYear(),
        currentTime.getMonth(),
        currentTime.getDate(),
        targetTime.getHours(),
        targetTime.getMinutes(),
        0  
      );
      let timeToWait = targetTimeToday.getTime() - currentTime.getTime();
      if (timeToWait < 0) {
        
        targetTimeToday.setDate(targetTimeToday.getDate() + 1);
        timeToWait = targetTimeToday.getTime() - currentTime.getTime();
      }
    
      setTimeout(() => {
        task();
         
        runAtSpecificTime(targetTime, task);
      }, timeToWait);
    };
    
     
    const targetTime = new Date();
    targetTime.setHours(17);  
    targetTime.setMinutes(24);  
    
     runAtSpecificTime(targetTime, callApi);
    
    
    // setInterval(() => {
     

    //     this.service.get('hr/employee.php?type=update_salary').subscribe(response => {
    //       this.salary = response;
    //     });
    
    // },10000); 
    // },5 * 60 * 60 * 1000); 
    // }, ); 

    this.service.observableUsername.subscribe(() => {
      this.username = this.service.username;
    });
  }

  goToDeptHomePage() {
    let loggedInDept = localStorage.getItem('department');
    this.service.navigateToSpecificDeptHome(loggedInDept);
  }


  ngOnInit(): void {
    this.loadPlantInfo();
    this.userId = localStorage.getItem('emp_id') || localStorage.getItem('loger_id') || '';
    this.userDepartment = this.resolveLoginDepartment();
    this.loadPendingNotifications();
    this.connectivitySub = this.connectivity.status$.subscribe((s) => {
      this.connectivityStatus = s;
      this.connectivityLabel =
        s === 'connected'
          ? this.translate.instant('common.connectivity.connected')
          : s === 'low'
          ? this.translate.instant('common.connectivity.weak')
          : this.translate.instant('common.connectivity.notConnected');
    });
    this.selectedLanguage = this.languageService.getCurrentLanguage();
    
    this.syncModuleBodyClass(this.router.url);
    this.routerSub = this.router.events.subscribe((event) => {
      if (event instanceof NavigationEnd) {
        this.syncModuleBodyClass(event.urlAfterRedirects || event.url);
      } else if (event instanceof NavigationError) {
        console.error('Route load failed', event.error);
      }
    });
  }

  get pendingNotificationCount(): number {
    return this.pendingNotifications.length;
  }

  get filteredNotifications(): PendingDocumentNotification[] {
    if (this.notificationFilter === 'All') {
      return this.pendingNotifications;
    }
    return this.pendingNotifications.filter((n) => n.actionType === this.notificationFilter);
  }

  openNotifications(): void {
    this.notificationFilter = 'All';
    this.loadPendingNotifications();
    this.showNotificationsModal = true;
    document.body.classList.add('nav-notif-open');
  }

  closeNotifications(): void {
    this.showNotificationsModal = false;
    document.body.classList.remove('nav-notif-open');
  }

  @HostListener('document:keydown.escape')
  onEscapeCloseNotifications(): void {
    if (this.showNotificationsModal) {
      this.closeNotifications();
    }
  }

  setNotificationFilter(filter: NotificationFilter): void {
    this.notificationFilter = filter;
  }

  countByAction(action: NotificationActionType): number {
    return this.pendingNotifications.filter((n) => n.actionType === action).length;
  }

  actionIcon(action: NotificationActionType): string {
    if (action === 'Checking') {
      return 'fa-clipboard-check';
    }
    if (action === 'Approval') {
      return 'fa-stamp';
    }
    return 'fa-search';
  }

  trackByNotificationId(_index: number, item: PendingDocumentNotification): string {
    return item.id;
  }

  openNotification(item: PendingDocumentNotification): void {
    if (!item?.route) {
      return;
    }
    this.closeNotifications();
    this.router.navigateByUrl(item.route).then((ok) => {
      if (ok === false) {
        alertify.error('Unable to open this notification route.');
      }
    }).catch(() => {
      alertify.error('Unable to open this notification route.');
    });
  }

  /**
   * Demo seed data for pending Checking / Approval / Review documents.
   * Uses the login department (not the last-opened dept card), so Master
   * users keep the full list after visiting Production / other modules.
   */
  loadPendingNotifications(): void {
    this.userDepartment = this.resolveLoginDepartment();
    const seed = this.getDemoPendingDocuments();
    const dept = (this.userDepartment || '').trim().toLowerCase();
    if (!dept || this.shouldShowAllNotifications(dept)) {
      this.pendingNotifications = seed;
      return;
    }
    const matched = seed.filter((n) => (n.department || '').trim().toLowerCase() === dept);
    // Keep demo useful even if department name differs slightly from seed labels
    this.pendingNotifications = matched.length > 0 ? matched : seed;
  }

  /** Login department — not overwritten when opening a department card. */
  private resolveLoginDepartment(): string {
    const loginDept = (localStorage.getItem('login_department') || '').trim();
    if (loginDept) {
      return loginDept;
    }
    const userDept = (localStorage.getItem('user_department') || '').trim();
    if (userDept && userDept !== 'All departments') {
      return userDept;
    }
    if (
      String(localStorage.getItem('loger_id') || '').toLowerCase() === 'master' ||
      localStorage.getItem('has_master_access') === 'Yes'
    ) {
      return 'Master';
    }
    return (localStorage.getItem('department') || '').trim();
  }

  private shouldShowAllNotifications(deptKey: string): boolean {
    if (!deptKey) {
      return true;
    }
    if (deptKey === 'master' || deptKey === 'masters' || deptKey === 'all departments') {
      return true;
    }
    return (
      String(localStorage.getItem('loger_id') || '').toLowerCase() === 'master' ||
      localStorage.getItem('has_master_access') === 'Yes'
    );
  }

  private getDemoPendingDocuments(): PendingDocumentNotification[] {
    return [
      {
        id: 'ntf-001',
        documentNo: 'DMS-REQ-2026-0142',
        title: 'SOP â€“ Equipment Cleaning Procedure (Document request approval)',
        module: 'Document Management',
        actionType: 'Approval',
        department: 'Quality Assurance',
        requestedBy: 'Priya Sharma',
        pendingSince: '2 days ago',
        priority: 'High',
        route: '/qa/document/request-approval',
      },
      {
        id: 'ntf-002',
        documentNo: 'DEV-2026-0088',
        title: 'Deviation â€“ Temperature excursion in Cold Storage Room-02',
        module: 'Deviation',
        actionType: 'Checking',
        department: 'Quality Assurance',
        requestedBy: 'Rahul Mehta',
        pendingSince: '1 day ago',
        priority: 'High',
        route: '/qa/deviation/checking',
      },
      {
        id: 'ntf-003',
        documentNo: 'CC-2026-0031',
        title: 'Change Control â€“ Update of packing line label artwork',
        module: 'Change Control',
        actionType: 'Checking',
        department: 'Quality Assurance',
        requestedBy: 'Ananya Iyer',
        pendingSince: '3 hours ago',
        priority: 'Medium',
        route: '/qa/changecontrol/checking',
      },
      {
        id: 'ntf-004',
        documentNo: 'CAPA-2026-0019',
        title: 'CAPA â€“ Corrective action for OOS investigation CAPA-19',
        module: 'CAPA',
        actionType: 'Review',
        department: 'Quality Assurance',
        requestedBy: 'Vikram Desai',
        pendingSince: '5 hours ago',
        priority: 'Medium',
        route: '/qa/capa/review',
      },
      {
        id: 'ntf-005',
        documentNo: 'SOP-REV-2026-0007',
        title: 'SOP Revision â€“ In-process sampling of tablets (checking)',
        module: 'SOP Revision',
        actionType: 'Checking',
        department: 'Quality Assurance',
        requestedBy: 'Neha Kulkarni',
        pendingSince: '4 days ago',
        priority: 'Low',
        route: '/qa/sops/revision/request-checking',
      },
      {
        id: 'ntf-006',
        documentNo: 'SOP-WA-2026-0024',
        title: 'SOP Writing Approval â€“ Method of Analysis for Assay',
        module: 'SOP Writing',
        actionType: 'Approval',
        department: 'Quality Assurance',
        requestedBy: 'Sandeep Rao',
        pendingSince: '6 hours ago',
        priority: 'High',
        route: '/qa/qms/sops/sop-writing-approval/my-tasks',
      },
      {
        id: 'ntf-007',
        documentNo: 'DEV-2026-0091',
        title: 'Deviation â€“ Batch yield variation above investigation limit',
        module: 'Deviation',
        actionType: 'Review',
        department: 'Quality Assurance',
        requestedBy: 'Kavita Nair',
        pendingSince: '8 hours ago',
        priority: 'High',
        route: '/qa/deviation/review',
      },
      {
        id: 'ntf-008',
        documentNo: 'CAPA-2026-0022',
        title: 'CAPA â€“ Preventive action for environmental monitoring trend',
        module: 'CAPA',
        actionType: 'Approval',
        department: 'Quality Assurance',
        requestedBy: 'Amit Joshi',
        pendingSince: '1 day ago',
        priority: 'Medium',
        route: '/qa/capa/approve',
      },
      {
        id: 'ntf-009',
        documentNo: 'QC-SPEC-2026-0055',
        title: 'Specification revision â€“ Finished product assay limits',
        module: 'Specification',
        actionType: 'Checking',
        department: 'Quality Control',
        requestedBy: 'Meera Patil',
        pendingSince: '2 days ago',
        priority: 'High',
        route: '/qc',
      },
      {
        id: 'ntf-010',
        documentNo: 'QC-OOS-2026-0012',
        title: 'OOS investigation checklist â€“ Batch FP-2407-018',
        module: 'OOS',
        actionType: 'Review',
        department: 'Quality Control',
        requestedBy: 'Arjun Singh',
        pendingSince: '12 hours ago',
        priority: 'High',
        route: '/qc/ooscheck',
      },
      {
        id: 'ntf-011',
        documentNo: 'PRD-BMR-2026-0044',
        title: 'BMR checklist â€“ Tablet compression stage review',
        module: 'Production',
        actionType: 'Review',
        department: 'Production',
        requestedBy: 'Deepak Verma',
        pendingSince: '3 days ago',
        priority: 'Medium',
        route: '/fproduction',
      },
      {
        id: 'ntf-012',
        documentNo: 'STR-GRN-2026-0278',
        title: 'GRN approval pending â€“ Raw material RM-API-778',
        module: 'Store',
        actionType: 'Approval',
        department: 'Store',
        requestedBy: 'Ritu Agarwal',
        pendingSince: '9 hours ago',
        priority: 'Medium',
        route: '/store',
      },
      {
        id: 'ntf-013',
        documentNo: 'ENG-EQ-2026-0015',
        title: 'Equipment qualification protocol â€“ HVAC AHU-07',
        module: 'Engineering',
        actionType: 'Checking',
        department: 'Engineering',
        requestedBy: 'Mohit Kapoor',
        pendingSince: '1 day ago',
        priority: 'Low',
        route: '/engineering',
      },
      {
        id: 'ntf-014',
        documentNo: 'HR-MR-2026-0063',
        title: 'Manpower requisition approval â€“ QC Analyst (Grade-II)',
        module: 'HR',
        actionType: 'Approval',
        department: 'Human Resource',
        requestedBy: 'Sneha Gupta',
        pendingSince: '2 days ago',
        priority: 'Medium',
        route: '/hr/recruitment/requisition',
      },
      {
        id: 'ntf-015',
        documentNo: 'PUR-IND-2026-0190',
        title: 'Purchase indent approval â€“ Primary packaging materials',
        module: 'Purchase',
        actionType: 'Approval',
        department: 'Purchase',
        requestedBy: 'Farhan Ali',
        pendingSince: '5 hours ago',
        priority: 'High',
        route: '/purchase',
      },
    ];
  }

  ngOnDestroy(): void {
    if (this.clockTimer != null) {
      clearInterval(this.clockTimer);
      this.clockTimer = null;
    }
    this.connectivitySub?.unsubscribe();
    this.routerSub?.unsubscribe();
    document.body.classList.remove('module-hr');
    document.body.classList.remove('module-store');
    document.body.classList.remove('module-qc');
    document.body.classList.remove('module-master');
    document.body.classList.remove('module-navy');
    document.body.classList.remove('nav-notif-open');
  }

  /** Apply module-wide header themes when browsing /hr/*, /store/*, or /qc/* routes */
  private syncModuleBodyClass(url: string): void {
    const path = (url || '').split('?')[0].split('#')[0];
    document.body.classList.toggle('module-hr', path === '/hr' || path.startsWith('/hr/'));
    document.body.classList.toggle('module-store', path === '/store' || path.startsWith('/store/'));
    const isMasterSpecHub = path === '/master/specification';
    const isQc =
      path === '/qc' ||
      path.startsWith('/qc/') ||
      path.startsWith('/master/specification/');
    document.body.classList.toggle('module-qc', isQc);
    document.body.classList.toggle(
      'module-master',
      isMasterSpecHub || (!isQc && (path === '/master' || path.startsWith('/master/')))
    );

    /* Navy theme â€” rolled out module by module */
    const navyPrefixes = [
      '/purchase',
      '/production',
      '/fproduction',
      '/production-formulation',
      '/prod-f-ebmr',
      '/qa',
      '/admin',
      '/management',
      '/marketing',
      '/engineering',
      '/engi-store',
      '/security',
      '/packing',
      '/unitformula',
      '/rnd',
      '/qms',
      '/ehs',
      '/ipqc',
      '/microbiology',
      '/planning',
      '/dispatch',
    ];
    const isNavy = navyPrefixes.some((p) => path === p || path.startsWith(p + '/'));
    document.body.classList.toggle('module-navy', isNavy);
  }

  loadPlantInfo() {
    this.plant_type = this.service.getPlantConfigFields('plant_type');
    this.plant_name = this.service.getPlantConfigFields('plant_name');
    const logoFromClientInfo = this.service.getPlantConfigFields('plant_logo');
    const logoFromSession = localStorage.getItem('logo_path');
    this.plant_logo = this.resolveLogoName(logoFromClientInfo, logoFromSession);
    this.logo_path = this.resolveLogoUrl(this.plant_logo);
  }

  private resolveLogoName(clientInfoLogo: any, sessionLogo: string | null): string {
    const clientLogo = String(clientInfoLogo || '').trim();
    const session = String(sessionLogo || '').trim();
    if (clientLogo) {
      return clientLogo;
    }
    if (session && session.toLowerCase() !== 'undefined' && session.toLowerCase() !== 'null') {
      return session;
    }
    return 'medicap-logo.png';
  }

  private resolveLogoUrl(logoName: string): string {
    const value = String(logoName || '').trim();
    const medicapLocal = 'assets/medicap-logo.png';
    if (!value) {
      return medicapLocal;
    }
    const lower = value.toLowerCase();
    // Always use the wide local Medicap logo (remote plant JPG is square and misaligns in the navbar pill)
    if (
      lower.indexOf('medicap') >= 0 ||
      /cyclone\.png$/i.test(value) ||
      /(?:^|\/)logo\.jpg$/i.test(value) ||
      /gmp\.jpe?g$/i.test(value) ||
      lower === 'gmp.jpg' ||
      lower === 'gmp.png' ||
      lower === 'logo.jpg' ||
      lower === 'logo.png' ||
      lower.indexOf('cyclone') >= 0
    ) {
      return medicapLocal;
    }
    if (value.startsWith('http://') || value.startsWith('https://') || value.startsWith('assets/')) {
      return value;
    }
    return 'https://aurenyxgmp.com/php/phpdevlop/gmptotal/logos/' + value;
  }

  onLogoError(event: Event): void {
    const img = event?.target as HTMLImageElement | null;
    if (!img) {
      return;
    }
    const fallback = 'assets/medicap-logo.png';
    if (!String(img.src || '').includes('medicap-logo.png')) {
      this.logo_path = fallback;
      img.src = fallback;
    }
  }
  toggleSidebar() {
    this.service.isSidebarOpen = !this.service.isSidebarOpen;
  }

  doLogout(): void {
    this.service.logout();
  }

  changeLanguage(langCode: string): void {
    this.languageService.applyLanguage(langCode);
    this.selectedLanguage = langCode;
    this.connectivityLabel =
      this.connectivityStatus === 'connected'
        ? this.translate.instant('common.connectivity.connected')
        : this.connectivityStatus === 'low'
        ? this.translate.instant('common.connectivity.weak')
        : this.translate.instant('common.connectivity.notConnected');
  }




    routeToDept(){

        let dept = localStorage.getItem('department');

        if(dept == 'Plant Head'){
            this.router.navigate(['/plant_head']);
        }else if(dept == 'Management'){
            this.router.navigate(['/management']);
        }else if(dept == 'master'){
            this.router.navigate(['/master']);
        }else if(dept == 'Admin'){
            this.router.navigate(['/admin']); 
        }else if(dept == 'Account'){
            this.router.navigate(['/accounts']); 
        }else if(dept == 'Marketing'){
            this.router.navigate(['/marketing']); 
        }else if(dept == 'Purchase'){
            this.router.navigate(['/purchase']); 
        }else if(dept == 'Human Resource'){
            this.router.navigate(['/hr']); 
        }else if(dept == 'Regulatory'){
            this.router.navigate(['/regulatory']); 
        }else if(dept == 'Security'){
            this.router.navigate(['/security']); 
        }else if(dept == 'Planning'){
            this.router.navigate(['/planning']); 
        }else if(dept == 'Production'){
            this.router.navigate(['/fproduction']); //production
        }else if(dept == 'Packing'){
            this.router.navigate(['/packing']);
        }else if(dept == 'Quality Control'){
            this.router.navigate(['/qc']);
        }else if(dept == 'Quality Assurance'){
            this.router.navigate(['/qa']);
        }else if(dept == 'Engineering'){
            this.router.navigate(['/engineering']);
        }else if(dept == 'IT'){
            this.router.navigate(['/it']);
        }else if(dept == 'EHS'){
            this.router.navigate(['/ehs']);
        }else if(dept == 'Store'){
            this.router.navigate(['/store']);
        }else if(dept == 'Dispatch'){
            this.router.navigate(['/dispatch']);
        }else if(dept == 'R AND D'){
            this.router.navigate(['/rnd']);
        }else if(dept == 'NPD'){
            this.router.navigate(['/npd']);
        }else{
          alertify.error('Please Contact Admin..');
        }
          
    }
 



 









}
