import { ChangeDetectorRef, Component, NgZone, OnDestroy, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from '../data-access.service';
import { SessionSecurityService } from '../shared/session-security/session-security.service';
declare let alertify;


@Component({
  selector: 'app-login',
  templateUrl: './login.component.html',
  styleUrls: ['./login.component.css'],
})
export class LoginComponent implements OnInit, OnDestroy {
  myDate: Date;
  plants;
  isLogin = false;
  isreset = false;
  isNewReset = false;
  isChangePin = false;
  submitting = false;
  lockoutSeconds = 0;
  /** Used for session-timeout reauth PIN (not login). */
  readonly defaultPin = '1234';
  readonly pinLength = 4;
  readonly maxAttempts = 3;
  readonly lockSeconds = 180;
  readonly passwordPolicyText =
    'Minimum 8 characters with uppercase, lowercase, number, and special character. No spaces.';
  changePinDigits: string[] = ['', '', '', ''];
  changePinConfirmDigits: string[] = ['', '', '', ''];
  changePinPassword = '';
  changePinEmpId = '';
  changePinAvailabilityMsg = '';
  selectedPlantId = '';
  selectedUsername = '';
  loginPassword = '';
  resetPlantId = '';
  resetEmpId = '';
  private clockTimer: ReturnType<typeof setInterval> | null = null;
  private lockoutTimer: ReturnType<typeof setInterval> | null = null;


  client_code = 'GMP22052'; // Medicap
  plant_id = 1126;
  readonly defaultPlantId = '1126';
  // client_code = 'GMP22027'; // Cyclone
  // plant_id = 142;

  loginData: any = [];
  new_password = '';
  confirm_password = '';
  constructor(
    public service: DataAccessService,
    private router: Router,
    private sessionSecurity: SessionSecurityService,
    private cdr: ChangeDetectorRef,
    private ngZone: NgZone
  ) {
    this.myDate = new Date();
    this.clockTimer = setInterval(() => {
      this.myDate = new Date();
    }, 1000);
  }

  ngOnDestroy(): void {
    if (this.clockTimer) {
      clearInterval(this.clockTimer);
      this.clockTimer = null;
    }
    this.clearLockoutTimer();
  }

  get selectedPlantLabel(): string {
    const id = this.plantOptionId(this.selectedPlantId);
    const p = (this.plants || []).find((x) => this.plantOptionId(x.plant_id) === id);
    return p?.display_name || '';
  }

  get resetPlantLabel(): string {
    const p = (this.plants || []).find((x) => String(x.plant_id) === String(this.resetPlantId));
    return p?.display_name || '';
  }

  onPlantChange(plantId: string | number): void {
    this.selectedPlantId = this.plantOptionId(plantId);
    if (this.selectedPlantId && typeof sessionStorage !== 'undefined') {
      sessionStorage.setItem('login_selected_plant_id', this.selectedPlantId);
    }
  }

  /** Normalize plant id from API / ngModel (number or string). */
  plantOptionId(id: unknown): string {
    if (id === null || id === undefined || id === '') {
      return '';
    }
    return String(id).trim();
  }

  private isHoPlant(plant: any): boolean {
    if (!plant) {
      return false;
    }
    const name = String(plant.display_name || plant.plant_name || '').trim().toUpperCase();
    const code = String(plant.plant_code || '').trim().toUpperCase();
    const id = this.plantOptionId(plant.plant_id);
    return name === 'HO' || code === 'HO' || id === '151' || id === '1127';
  }

  /** Medicap (1126) default; fallback to first visible login plant. */
  private resolveDefaultPlantId(allPlants: any[]): string {
    const preferred = this.plantOptionId(this.defaultPlantId || this.plant_id);
    const visible = this.filterPlantsForMedicapLogin(allPlants);
    if (visible.some((p) => this.plantOptionId(p.plant_id) === preferred)) {
      return preferred;
    }
    return visible.length ? this.plantOptionId(visible[0].plant_id) : preferred;
  }

  private ensureSelectablePlantId(currentId: string, allPlants: any[]): string {
    const id = this.plantOptionId(currentId);
    const visible = this.filterPlantsForMedicapLogin(allPlants);
    if (id && visible.some((p) => this.plantOptionId(p.plant_id) === id)) {
      return id;
    }
    return this.resolveDefaultPlantId(allPlants);
  }

  /** Hide HO and collapse duplicate Medicap rows to canonical plant 1126. */
  private filterPlantsForMedicapLogin(allPlants: any[]): any[] {
    const withoutHo = (allPlants || []).filter((p) => !this.isHoPlant(p));
    const medicapRows = withoutHo.filter((p) => {
      const id = this.plantOptionId(p.plant_id);
      if (id === this.defaultPlantId) {
        return true;
      }
      const label = String(p.display_name || p.plant_name || '').toLowerCase();
      return label.includes('medicap');
    });

    if (medicapRows.length <= 1) {
      return medicapRows.length ? medicapRows : withoutHo;
    }

    const canonical =
      medicapRows.find((p) => this.plantOptionId(p.plant_id) === this.defaultPlantId) ||
      medicapRows[0];
    return canonical ? [canonical] : medicapRows;
  }

  /** Plant for change PIN / login — sync from dropdown state or defaults. */
  resolvePlantId(): string {
    const fromState = this.plantOptionId(this.selectedPlantId);
    if (fromState) {
      return fromState;
    }

    const sel = document.querySelector('.login-form select[name="plant_id"]') as HTMLSelectElement | null;
    if (sel && sel.selectedIndex > 0 && this.plants?.length) {
      const plant = this.plants[sel.selectedIndex - 1];
      const fromDom = this.plantOptionId(plant?.plant_id);
      if (fromDom) {
        return fromDom;
      }
    }

    if (typeof sessionStorage !== 'undefined') {
      const fromSession = this.plantOptionId(sessionStorage.getItem('login_selected_plant_id'));
      if (fromSession) {
        return fromSession;
      }
    }

    const defaultId = this.plantOptionId(this.defaultPlantId || this.plant_id);
    if (defaultId) {
      return defaultId;
    }
    if (this.plants?.length) {
      return this.plantOptionId(this.plants[0].plant_id);
    }
    return '';
  }

  syncSelectedPlantFromLogin(): string {
    const id = this.resolvePlantId();
    if (id) {
      this.selectedPlantId = id;
    }
    return id;
  }

  get resolvedPlantLabel(): string {
    const id = this.resolvePlantId();
    const p = (this.plants || []).find((x) => this.plantOptionId(x.plant_id) === id);
    return p?.display_name || '';
  }

  onResetPlantChange(plantId: string): void {
    this.resetPlantId = plantId ? String(plantId) : '';
  }

  onChangePinEmpIdChange(empId: string): void {
    this.changePinEmpId = (empId || '').trim();
    this.afterChangePinDigitsUpdate();
  }

  private validatePinAvailability(pin: string, empId: string): void {
    if (pin.length !== this.pinLength) {
      this.changePinAvailabilityMsg = '';
      return;
    }
    const plantId = this.syncSelectedPlantFromLogin();
    const emp = (empId || '').trim();
    if (!plantId) {
      this.changePinAvailabilityMsg = '';
      return;
    }
    if (!emp) {
      this.changePinAvailabilityMsg = '';
      return;
    }
    this.service.checkPinAvailable(pin, {
      empId: emp,
      plantId,
    }).subscribe({
      next: (r: any) => {
        this.changePinAvailabilityMsg =
          r?.status === 'available' ? '' : (r?.message || 'PIN is not available to set.');
      },
      error: () => {
        /* ignore live check errors */
      },
    });
  }

  private afterChangePinDigitsUpdate(): void {
    const pin = this.readPinValue('change-pin', this.changePinDigits);
    if (pin.length === this.pinLength) {
      this.validatePinAvailability(pin, this.changePinEmpId);
    } else {
      this.changePinAvailabilityMsg = '';
    }
  }

  /** Read PIN from component state or modal DOM inputs (Change PIN / session PIN only). */
  private readPinValue(prefix: string, digits: string[]): string {
    let fromDom = '';
    for (let i = 0; i < this.pinLength; i++) {
      const el = document.getElementById(`${prefix}-${i}`) as HTMLInputElement | null;
      fromDom += (el?.value || '').replace(/\D/g, '').slice(-1);
    }
    if (fromDom.length === this.pinLength) {
      return fromDom;
    }
    const fromState = digits.join('');
    return fromState.length === this.pinLength ? fromState : fromDom;
  }

  pinTrackBy = (index: number): number => index;

  private syncPinDom(prefix: string, digits: string[]): void {
    for (let i = 0; i < this.pinLength; i++) {
      const el = document.getElementById(`${prefix}-${i}`) as HTMLInputElement | null;
      if (el) {
        el.value = digits[i] || '';
      }
    }
  }

  private focusPinCell(prefix: string, index: number): void {
    setTimeout(() => {
      const el = document.getElementById(`${prefix}-${index}`) as HTMLInputElement | null;
      el?.focus();
      el?.select();
    }, 0);
  }

  private setPinCell(prefix: string, index: number, digit: string, digits: string[]): void {
    digits[index] = digit;
    const el = document.getElementById(`${prefix}-${index}`) as HTMLInputElement | null;
    if (el) {
      el.value = digit;
    }
  }

  private handlePinInput(
    prefix: string,
    digits: string[],
    index: number,
    event: Event,
    afterUpdate?: () => void
  ): void {
    const input = event.target as HTMLInputElement;
    const raw = (input.value || '').replace(/\D/g, '');
    const last = this.pinLength - 1;
    if (raw.length > 1) {
      for (let i = 0; i < this.pinLength; i++) {
        this.setPinCell(prefix, i, raw[i] || '', digits);
      }
      this.focusPinCell(prefix, Math.min(raw.length, this.pinLength) - 1);
      afterUpdate?.();
      return;
    }
    const val = raw.slice(-1);
    this.setPinCell(prefix, index, val, digits);
    if (val && index < last) {
      this.focusPinCell(prefix, index + 1);
    }
    afterUpdate?.();
  }

  private handlePinKeydown(
    prefix: string,
    digits: string[],
    index: number,
    event: KeyboardEvent,
    afterUpdate?: () => void
  ): void {
    const key = event.key;
    const last = this.pinLength - 1;
    if (key >= '0' && key <= '9') {
      event.preventDefault();
      this.setPinCell(prefix, index, key, digits);
      if (index < last) {
        this.focusPinCell(prefix, index + 1);
      }
      afterUpdate?.();
      return;
    }
    if (key === 'Backspace' || key === 'Delete') {
      event.preventDefault();
      if (digits[index]) {
        this.setPinCell(prefix, index, '', digits);
      } else if (index > 0) {
        this.setPinCell(prefix, index - 1, '', digits);
        this.focusPinCell(prefix, index - 1);
      }
      afterUpdate?.();
      return;
    }
    if (key === 'ArrowLeft' && index > 0) {
      event.preventDefault();
      this.focusPinCell(prefix, index - 1);
      return;
    }
    if (key === 'ArrowRight' && index < last) {
      event.preventDefault();
      this.focusPinCell(prefix, index + 1);
    }
  }

  private handlePinPaste(
    prefix: string,
    digits: string[],
    event: ClipboardEvent,
    afterUpdate?: () => void
  ): void {
    event.preventDefault();
    const text = (event.clipboardData?.getData('text') || '').replace(/\D/g, '').slice(0, this.pinLength);
    for (let i = 0; i < this.pinLength; i++) {
      this.setPinCell(prefix, i, text[i] || '', digits);
    }
    afterUpdate?.();
    this.focusPinCell(prefix, text.length > 0 ? Math.min(text.length, this.pinLength) - 1 : 0);
  }

  onChangePinInput(i: number, e: Event): void {
    this.handlePinInput('change-pin', this.changePinDigits, i, e, () => this.afterChangePinDigitsUpdate());
  }
  onChangePinKeydown(i: number, e: KeyboardEvent): void {
    this.handlePinKeydown('change-pin', this.changePinDigits, i, e, () => this.afterChangePinDigitsUpdate());
  }
  onChangePinPaste(e: ClipboardEvent): void {
    this.handlePinPaste('change-pin', this.changePinDigits, e, () => this.afterChangePinDigitsUpdate());
  }
  onChangePinConfirmInput(i: number, e: Event): void {
    this.handlePinInput('change-pin-c', this.changePinConfirmDigits, i, e);
  }
  onChangePinConfirmKeydown(i: number, e: KeyboardEvent): void {
    this.handlePinKeydown('change-pin-c', this.changePinConfirmDigits, i, e);
  }
  onChangePinConfirmPaste(e: ClipboardEvent): void {
    this.handlePinPaste('change-pin-c', this.changePinConfirmDigits, e);
  }

  openForgotPassword(): void {
    this.resetPlantId = this.selectedPlantId || this.resetPlantId;
    this.resetEmpId = (this.selectedUsername || '').trim();
    this.isreset = true;
  }

  openChangePin(): void {
    this.syncSelectedPlantFromLogin();
    this.changePinEmpId = (this.selectedUsername || '').trim();
    this.changePinPassword = '';
    this.changePinDigits.fill('');
    this.changePinConfirmDigits.fill('');
    this.changePinAvailabilityMsg = '';
    this.showChangePin = false;
    this.showChangePinConfirm = false;
    this.isChangePin = true;
    setTimeout(() => {
      this.syncPinDom('change-pin', this.changePinDigits);
      this.syncPinDom('change-pin-c', this.changePinConfirmDigits);
      this.focusPinCell('change-pin', 0);
    }, 100);
  }

  startLockoutCountdown(seconds: number): void {
    this.lockoutSeconds = Math.max(0, Math.floor(seconds));
    this.clearLockoutTimer();
    if (this.lockoutSeconds <= 0) {
      return;
    }
    this.lockoutTimer = setInterval(() => {
      this.lockoutSeconds = Math.max(0, this.lockoutSeconds - 1);
      if (this.lockoutSeconds <= 0) {
        this.clearLockoutTimer();
      }
    }, 1000);
  }

  private clearLockoutTimer(): void {
    if (this.lockoutTimer) {
      clearInterval(this.lockoutTimer);
      this.lockoutTimer = null;
    }
  }

  private finalizeLoginStorage(response: any, plantId: string): void {
    this.sessionSecurity.markLoginSuccess();
    localStorage.setItem('company_name', 'Project By Aurenyx Pharmatech Pvt Ltd');
  }


  newPassword = '';
  confirmPassword = '';


  showLoginPassword: boolean = false;
  showChangePin = false;
  showChangePinConfirm = false;
  passwordsMatch: boolean = true;

  toggleShowPassword(field: 'login'): void {
    if (field === 'login') {
      this.showLoginPassword = !this.showLoginPassword;
    }
  }

  togglePinVisibility(target: 'change' | 'changeConfirm'): void {
    switch (target) {
      case 'change':
        this.showChangePin = !this.showChangePin;
        setTimeout(() => this.syncPinDom('change-pin', this.changePinDigits));
        break;
      case 'changeConfirm':
        this.showChangePinConfirm = !this.showChangePinConfirm;
        setTimeout(() => this.syncPinDom('change-pin-c', this.changePinConfirmDigits));
        break;
    }
  }

  validatePasswords() {
    this.passwordsMatch = this.new_password === this.confirm_password;
  }


  validatePasswords1() {
    this.passwordsMatch = this.newPassword === this.confirmPassword;
  }






  ngOnInit() {
    // Medicap local app must use live PHP/DB (Master + PIN live there).
    this.service.setApiMode('server');
    this.service.syncServerConnection();
    localStorage.setItem('api_mode', 'server');
    localStorage.setItem('client_code', this.client_code || 'GMP22052');
    // Always have a plant option so Login is never blocked by empty dropdown.
    this.plants = [
      { plant_id: '1126', display_name: 'Medicap Laboratories', plant_name: 'Medicap Laboratories' },
    ];
    this.selectedPlantId = this.defaultPlantId;
    if (typeof sessionStorage !== 'undefined') {
      sessionStorage.setItem('login_selected_plant_id', this.defaultPlantId);
    }
    this.validateUser();
    this.getPlant();
  }

  ho_plant_id: string = '1127';

  private safeStore(key: string, value: unknown): void {
    try {
      localStorage.setItem(key, value === null || value === undefined ? '' : String(value));
    } catch {
      /* ignore quota / private mode */
    }
  }

  getPlant() {
    this.service.loadLoginPlants(this.client_code).subscribe(
      (response) => {
        const allPlants = Array.isArray(response) ? response : [];
        if (!allPlants.length) {
          alertify.warning(
            this.service.apiMode === 'server'
              ? 'Plant list empty. Check internet connection and refresh (Ctrl+F5).'
              : 'Plant list empty. Start XAMPP Apache + MySQL and refresh.'
          );
          return;
        }

        const hoPlant = allPlants.find((plant) => this.isHoPlant(plant));
        if (hoPlant) {
          this.ho_plant_id = this.plantOptionId(hoPlant.plant_id) || '1127';
        } else {
          this.ho_plant_id = '1127';
        }

        this.plants = this.filterPlantsForMedicapLogin(allPlants);
        if (!this.plants.length) {
          alertify.warning('No plants available for login.');
          return;
        }

        localStorage.setItem('all_plants', JSON.stringify(allPlants));

        this.selectedPlantId = this.ensureSelectablePlantId(this.selectedPlantId, allPlants);
        if (typeof sessionStorage !== 'undefined') {
          sessionStorage.setItem('login_selected_plant_id', this.selectedPlantId);
        }
        this.resetPlantId = this.ensureSelectablePlantId(this.resetPlantId, allPlants);
      },
      () => {
        alertify.error(
          this.service.apiMode === 'local'
            ? 'Could not load plants from server. Start XAMPP Apache + MySQL, then refresh (Ctrl+F5).'
            : 'Could not load plants. Check your internet connection.'
        );
      }
    );
  }


  checkLogin(formData) {
    if (this.lockoutSeconds > 0) {
      alertify.error(`Account locked. Try again in ${this.lockoutSeconds} seconds.`);
      return;
    }
    // Prefer DOM values (browser autofill often skips ngModel).
    const username = (this.selectedUsername || formData?.value?.username || '').trim();
    const pwEl = document.getElementById('loginPassword') as HTMLInputElement | null;
    const password = String(pwEl?.value || this.loginPassword || formData?.value?.password || '').trim();
    this.selectedUsername = username;
    this.loginPassword = password;

    if (!username || !password) {
      alertify.error('Enter username and password.');
      return;
    }

    const plantId = this.plantOptionId(this.selectedPlantId) || this.defaultPlantId;
    if (!plantId) {
      alertify.error('Please select a plant.');
      return;
    }
    this.selectedPlantId = plantId;
    localStorage.removeItem('force_local_php');
    this.service.setApiMode('server');
    this.service.syncServerConnection();
    const apiUrl = String(this.service.url || '');
    const wiredToLive =
      apiUrl.includes('/php/phpdevlop/phpmedicap') ||
      apiUrl.includes('aurenyxgmp.com/php/');
    if (!wiredToLive) {
      alertify.error('API not connected to live PHP. Refresh (Ctrl+Shift+R) and try again.');
      return;
    }

    this.submitting = true;
    const loginMode = 'password';
    const data: any = {
      plant_id: plantId,
      username,
      password,
      login_mode: loginMode,
    };
    this.loginData = data;
    this.service
      .login(
        'checkLogin.php?type=newLogin&login_mode=' +
          encodeURIComponent(loginMode) +
          '&username=' +
          encodeURIComponent(data.username || '') +
          '&plant_id=' +
          encodeURIComponent(data.plant_id),
        JSON.stringify(data)
      )
      .subscribe({
        next: (response) => {
          this.ngZone.run(() => this.handleLoginResponse(response, data));
        },
        error: (err) => {
          this.ngZone.run(() => {
            this.submitting = false;
            const msg =
              err?.status === 0
                ? 'Cannot reach live PHP via proxy. Keep ng serve running and refresh.'
                : `Server error (${err?.status}). Check PHP backend and database connection.`;
            alertify.error(msg);
            this.isLogin = false;
            this.cdr.detectChanges();
          });
        },
      });
  }

  /** Sync handler — no async/await (avoids stuck login screen on local ng serve). */
  private handleLoginResponse(response: any, data: any): void {
    this.submitting = false;
    if (!response || typeof response !== 'object') {
      alertify.error('Invalid login response from server.');
      this.isLogin = false;
      this.cdr.detectChanges();
      return;
    }
    if (response['status'] === 'locked') {
      this.startLockoutCountdown(Number(response['retry_after_seconds'] || this.lockSeconds));
      alertify.error(response['message'] || 'Account locked.');
      this.isLogin = false;
      this.cdr.detectChanges();
      return;
    }
    if (response['status'] === 'invalid') {
      alertify.error(response['message'] || 'Invalid credentials.');
      this.isLogin = false;
      this.cdr.detectChanges();
      return;
    }
    if (response['status'] === 'success') {
      if (response['ISNEW'] == 'YES') {
        this.isNewReset = true;
        this.safeStore('plant_id', response['plant_id']);
        this.safeStore('client_code', this.client_code || 'GMP22052');
        this.cdr.detectChanges();
        return;
      }
      this.applyEmployeeLoginSession(response, data);
      this.enterAppAfterLogin(response, data);
      return;
    }
    if (response['status'] === 'success1') {
      this.safeStore('token', response['token']);
      this.safeStore('plant_id', response['plant_id']);
      this.safeStore('department', response['department']);
      this.safeStore('login_department', response['department'] || 'Master');
      this.safeStore('loger_id', response['loger_id']);
      this.safeStore('ho_plant_id', this.ho_plant_id || '1127');
      this.safeStore('username', response['username']);
      this.safeStore('type', response['type']);
      this.finalizeLoginStorage(response, response['plant_id']);
      this.safeStore('client_code', this.client_code || 'GMP22052');
      this.safeStore('login', 'yes');
      this.service.applyClientInfoFromLogin(response, response['plant_id']);
      this.getPlantsFromMasterData(response);
      this.isLogin = true;
      this.cdr.detectChanges();
      this.checkDept(response['department']);
      return;
    }
    if (response['status'] === 'success2') {
      this.safeStore('token', response['token']);
      this.safeStore('plant_id', response['plant_id']);
      this.safeStore('department', response['department']);
      this.safeStore('login_department', response['department'] || 'Master');
      this.safeStore('logo_path', response['logo_path']);
      this.safeStore('licence_no', response['licence_no']);
      this.safeStore('loger_id', response['loger_id']);
      this.safeStore('ho_plant_id', this.ho_plant_id || '1127');
      this.safeStore('username', response['username']);
      this.safeStore('plant_name', response['username']);
      this.safeStore('plant_type', response['loger_id']);
      this.safeStore('type', 'vendor');
      this.finalizeLoginStorage(response, response['plant_id']);
      this.safeStore('login', 'yes');
      this.safeStore('isVendor', 'YES');
      this.safeStore('vendor_no', response['loger_id']);
      this.isVendor = 'YES';
      this.isLogin = true;
      this.cdr.detectChanges();
      this.checkDept(response['department']);
      return;
    }
    if (response['status'] === 'db_error') {
      alertify.error(response['message'] || 'Database connection failed.');
    } else if (response['status'] === 'php_error') {
      alertify.error(response['message'] || 'PHP error.');
    } else {
      alertify.error(response['status'] || 'Login failed.');
    }
    this.isLogin = false;
    this.cdr.detectChanges();
  }

  private applyEmployeeLoginSession(response: any, data: any): void {
    // Stamp session times FIRST so idle logout cannot fire mid-login.
    this.finalizeLoginStorage(response, data['plant_id']);
    this.safeStore('token', response['token']);
    this.safeStore('plant_id', data['plant_id'] || response['plant_id'] || '1126');
    this.safeStore('is_corporate', response['is_corporate'] ?? '0');
    this.safeStore('department', response['department'] || 'Master');
    this.safeStore('login_department', response['department'] || 'Master');
    this.safeStore('logo_path', response['logo_path'] || '');
    this.safeStore('licence_no', response['licence_no'] || '');
    this.safeStore('loger_id', response['loger_id'] || '');
    this.safeStore('emp_id', response['loger_id'] || '');
    this.safeStore('hrView', response['hrView'] ?? '');
    this.safeStore('ho_plant_id', this.ho_plant_id || '1127');
    this.safeStore('designation', response['designation'] || '');
    this.safeStore('user', response['user'] || 'Yes');
    this.safeStore('checker', response['checker'] || 'No');
    this.safeStore('approver', response['approver'] || 'No');
    this.safeStore('qms_approver', response['qms_approver'] || 'No');
    this.safeStore('dept_head', response['dept_head'] || 'No');
    this.safeStore('username', response['username'] || '');
    this.safeStore(
      'user_department',
      String(data['username'] || response['loger_id'] || '').toLowerCase() === 'master'
        ? 'All departments'
        : response['department'] || 'Master'
    );
    this.safeStore('user_no', 'gmpdemo1');
    this.safeStore('type', response['type'] || 'employee');
    this.safeStore('client_code', this.client_code || 'GMP22052');
    this.safeStore('has_master_access', response['has_master_access'] || 'Yes');
    this.safeStore('plant_name', 'Medicap Laboratories');
    this.safeStore('all_plants', JSON.stringify(this.plants || []));
    this.service.applyClientInfoFromLogin(response, data['plant_id'] || '1126');
    this.safeStore('login', 'yes');
    this.service.username = response['username'];
    try {
      this.service.userChange();
    } catch {
      /* non-fatal */
    }
    // Fire-and-forget — must not block UI enter.
    try {
      this.getPlantsFromMasterData(response);
    } catch {
      /* non-fatal */
    }
    try {
      this.service.getCommonDetails();
    } catch {
      /* non-fatal */
    }
    if (Array.isArray(response?.all_rights) && response.all_rights.length) {
      this.service.cacheEmpRights(response.all_rights);
    } else if (response?.data) {
      this.service.cacheEmpRights([response.data]);
    }
  }

  private enterAppAfterLogin(response: any, data: any): void {
    this.isLogin = true;
    this.isNewReset = false;
    try {
      alertify.success('Login successful');
    } catch {
      /* ignore */
    }
    this.cdr.detectChanges();
    setTimeout(() => {
      this.isLogin = true;
      this.cdr.detectChanges();
      try {
        this.redirectAfterLogin(
          response,
          response['department'],
          data['username'] || response['loger_id'] || 'Master',
          data['plant_id'] || '1126'
        );
      } catch (e) {
        console.error('redirectAfterLogin failed', e);
        this.router.navigate(['/'], { replaceUrl: true });
      }
      this.cdr.detectChanges();
    }, 0);
  }

  isVendor = 'NO';

  async getPlantsFromMasterData(loginResponse?: Record<string, unknown>) {
    const plantId = localStorage.getItem('plant_id') || '';
    this.service.applyClientInfoFromLogin(loginResponse, plantId);
    return new Promise((resolve) => {
      this.service.refreshClientInfo(plantId, loginResponse).subscribe({
        next: (res) => {
          if (localStorage.getItem('isVendor') !== 'YES') {
            localStorage.setItem('client_info', JSON.stringify(res));
          }
          resolve(res);
        },
        error: () => resolve(this.service.buildClientInfoPayload(plantId, loginResponse)),
      });
    });
  }

  validateUser() {
    const loginAt = Number(localStorage.getItem(SessionSecurityService.LOGIN_AT_KEY) || '0');
    if (
      loginAt > 0 &&
      Date.now() - loginAt >= 3 * 60 * 60 * 1000 &&
      localStorage.getItem('login') === 'yes'
    ) {
      alertify.warning('Session expired after 3 hours. Please login again.');
      this.service.logout();
      return;
    }
    // Require explicit login flag — leftover token alone must not half-enter the app.
    if (
      localStorage.getItem('login') === 'yes' &&
      localStorage.getItem('token') &&
      localStorage.getItem('department')
    ) {
      this.isLogin = true;
      this.getPlantsFromMasterData();
      this.service.getCommonDetails();
      this.cdr.detectChanges();
    } else {
      this.isLogin = false;
      this.cdr.detectChanges();
    }
  }

  reset(data) {
    if (!data.valid) {
      alertify.error('All fields are required!');
      return;
    }
    if (!this.passwordsMatch) {
      alertify.error('Passwords do not match.');
      return;
    }

    this.service.post('checkLogin.php?type=forgotPassword', JSON.stringify(data.value))
      .subscribe((response) => {
        if (response['status'] == 'success') {
          alertify.success('Password reset request sent for IT approval.');
          this.isreset = false;
          data.reset();
          this.new_password = '';
          this.confirm_password = '';
        }
        else if (response['status'] == 'NotFound') {
          alertify.error('Employee not found. Enter a valid Employee ID.');
        }
        else {
          alertify.error(response['message'] || 'Failed. Please try again.');
        }
      });
  }

  submitChangePin(): void {
    const plantId = this.syncSelectedPlantFromLogin();
    const empId = (this.changePinEmpId || this.selectedUsername || '').trim();
    const newPin = this.readPinValue('change-pin', this.changePinDigits);
    const confirmPin = this.readPinValue('change-pin-c', this.changePinConfirmDigits);
    const password = (this.changePinPassword || '').trim();

    if (!empId) {
      alertify.error('Enter your Employee ID / Username.');
      return;
    }
    if (!password) {
      alertify.error('Enter your current login password.');
      return;
    }
    if (newPin.length !== this.pinLength || confirmPin.length !== this.pinLength) {
      alertify.error('Enter and confirm your new 4-digit PIN.');
      return;
    }
    if (newPin !== confirmPin) {
      alertify.error('New PIN and confirm PIN do not match.');
      return;
    }
    if (this.changePinAvailabilityMsg) {
      alertify.error(this.changePinAvailabilityMsg);
      return;
    }

    const payload: Record<string, string> = {
      emp_id: empId,
      current_password: password,
      new_pin: newPin,
      confirm_pin: confirmPin,
    };
    if (plantId) {
      payload.plant_id = plantId;
    }

    this.service
      .postUrlEncoded('checkLogin.php?type=changePinLogin', payload)
      .subscribe((response) => {
        if (response['status'] === 'success') {
          alertify.success(response['message'] || 'PIN updated successfully.');
          this.isChangePin = false;
          this.changePinDigits.fill('');
          this.changePinConfirmDigits.fill('');
          this.changePinPassword = '';
          this.changePinAvailabilityMsg = '';
        } else {
          alertify.error(response['message'] || 'Could not update PIN.');
        }
      });
  }


  setPlantId(){
    localStorage.setItem('plant_id', String(this.selectedPlantId || this.plant_id || ''));
  }

  resetPassword(data) {
    if (!data.valid) {
      alertify.error('All fields are required!');
      return;
    }
    let temp = data.value;

    if (temp['new_password'] !== temp['confirm_password']) {
      alertify.error('Invalid Confirm Password!');
      return;
    }

    temp['emp_id'] = this.loginData['username'];
    temp['confirm_password'] = temp['confirm_password'] || temp['new_password'];
    this.service
      .post('checkLogin.php?type=resetPassword', JSON.stringify(temp))
      .subscribe((response) => {
        if (response['status'] == 'success') {
          alertify.success('Password has been updated!');
          this.isNewReset = false;
          data.reset();
        } else {
          alertify.error(response['message'] || 'Failed. Please try again.');
        }
      });
  }

  /**
   * Post-login routing for normal employees:
   *  - Master users  -> main hub ('/')
   *  - Exactly one (mappable) department -> that department's dashboard
   *  - Multiple departments -> main hub (user picks which to open)
   *  - Ambiguous/unmapped (e.g. Production routing varies by plant) -> main hub
   */
  private redirectAfterLogin(
    response: any,
    homeDept: string,
    username: string,
    loginPlantId?: string
  ) {
    const rows = Array.isArray(response?.all_rights) ? response.all_rights : [];
    const plantId = String(loginPlantId || localStorage.getItem('plant_id') || '').trim();
    const hoId = String(this.ho_plant_id || '151').trim();
    const hasMarketing = rows.some((r) => String(r?.department || '').trim() === 'Marketing');

    // HO login: typical workflow is Marketing PO/FO — open Marketing when user has rights.
    if (plantId === hoId && hasMarketing) {
      localStorage.setItem('department', 'Marketing');
      this.router.navigate(['/marketing'], { replaceUrl: true });
      return;
    }

    const isMaster =
      localStorage.getItem('has_master_access') === 'Yes' ||
      String(username || '').toLowerCase() === 'master' ||
      rows.some((r) => {
        const d = String(r?.department || '').toLowerCase();
        return d === 'master' || d === 'masters' || r?.plant_head === 'Yes';
      });

    if (isMaster) {
      this.router.navigate(['/'], { replaceUrl: true });
      return;
    }

    const deptNames: string[] = rows
      .map((r) => String(r?.department || '').trim())
      .filter(
        (d: string) =>
          d !== '' &&
          d.toLowerCase() !== 'master' &&
          d.toLowerCase() !== 'masters'
      );
    const depts: string[] = Array.from(new Set<string>(deptNames));

    let targetDept = '';
    if (depts.length === 1) {
      targetDept = depts[0];
    } else if (depts.length === 0 && homeDept) {
      targetDept = homeDept;
    }
    // depts.length > 1  -> fall through to main hub

    if (targetDept) {
      const route = this.deptHomeRoute(targetDept);
      if (route) {
        localStorage.setItem('department', targetDept);
        this.router.navigate([route], { replaceUrl: true });
        return;
      }
    }
    this.router.navigate(['/'], { replaceUrl: true });
  }

  /** Map a stored department name to its dashboard route, or null when ambiguous. */
  private deptHomeRoute(deptName: string): string | null {
    const d = String(deptName || '')
      .trim()
      .toLowerCase()
      .replace(/\s+/g, ' ');
    const map: Record<string, string> = {
      'human resource': '/hr',
      hr: '/hr',
      'quality assurance': '/qa',
      qa: '/qa',
      'quality control': '/qc',
      qc: '/qc',
      microbiology: '/microbiology',
      engineering: '/engineering',
      'engineering store': '/engi-store',
      'engineering stores': '/engi-store',
      'engi-store': '/engi-store',
      planning: '/planning',
      purchase: '/purchase',
      account: '/account',
      accounts: '/account',
      admin: '/admin',
      administration: '/admin',
      security: '/security',
      ipqc: '/ipqc',
      it: '/it',
      'information technology': '/it',
      dispatch: '/dispatch',
      ehs: '/ehs',
      marketing: '/marketing',
      'marketing/bd': '/marketing',
      'marketing / bd': '/marketing',
      packing: '/packing',
      'r and d': '/rnd',
      rnd: '/rnd',
      'r&d': '/rnd',
      management: '/management',
      calibration: '/calibration',
      store: '/store',
      stores: '/store',
    };
    return map[d] || null;
  }

  checkDept(value) {
    console.log('Department name', value);
    // if (value == 'master') {
    this.router.navigate(['/'], { replaceUrl: true });
    // }else if (value == 'Management') {
    //   this.router.navigate(['/management']);
    // } else if (value == 'Hr') {
    //   this.router.navigate(['/']);
    // } else if (value == 'Export') {
    //   this.router.navigate(['/export']);
    // } else if (value == 'Vendor-dasbhoard') {
    //   this.router.navigate(['/vendor-dasbhoard']);
    // } else if (value == 'Ehs') {
    //   this.router.navigate(['/ehs']);
    // } else if (value == 'Marketing') {
    //   this.router.navigate(['/marketing']);
    // } else if (value == 'Vendor') {
    //   this.router.navigate(['/vendor']);
    // } else if (value == 'Account') {
    //   this.router.navigate(['/account']);
    // } else if (value == 'Admin') {
    //   this.router.navigate(['/admin']);
    // } else if (value == 'Planning') {
    //   this.router.navigate(['/planning']);
    // } else if (value == 'Purchase') {
    //   this.router.navigate(['/purchase']);
    // }else if (value == 'Autopurchase') {
    //   this.router.navigate(['/autopurchase']);
    // }else if (value == 'Security') {
    //   this.router.navigate(['/security']);
    // } else if (value == 'Store') {
    //   this.router.navigate(['/store']);
    // } else if (value == 'Production') {
    //   this.router.navigate(['/fproduction']);
    // } else if (value == 'Production2') {
    //   this.router.navigate(['/production2']);
    // } else if (value == 'packing') {
    //   this.router.navigate(['/packing']);
    // } else if (value == 'Dispatch') {
    //   this.router.navigate(['/dispatch']);
    // } else if (value == 'It') {
    //   this.router.navigate(['/it']);
    // } else if (value == 'Quality Control') {
    //   this.router.navigate(['/qc']);
    // } else if (value == 'Ipqc') {
    //   this.router.navigate(['/ipqc']);
    // } else if (value == 'Quality Assurance') {
    //   this.router.navigate(['/qa']);
    // } else if (value == 'Microbiology') {
    //   this.router.navigate(['/microbiology']);
    // } else if (value == 'Engi-store') {
    //   this.router.navigate(['/engi-store']);
    // } else if (value == 'Engineering') {
    //   this.router.navigate(['/engineering']);
    // } else if (value == 'Calibration') {
    //   this.router.navigate(['/calibration']);
    // } else if (value == 'Rnd') {
    //   this.router.navigate(['/rnd']);
    // } else if (value == 'Sales-force') {
    //   this.router.navigate(['/sales-force']);
    // } else if (value == 'Regulatory') {
    //   this.router.navigate(['/regulatory']);
    // } else if (value == 'Vendor-dashboard') {
    //   this.router.navigate(['/vendor-dashboard']);
    // }
    // else if (value == 'Fnd') {
    //   this.router.navigate(['/fnd']);
    // }
    // else if (value == 'NA') {
    //   this.router.navigate(['/ll-cm']);
    // }
    // else if (value == 'Vendor-panel') {
    //   if (localStorage.getItem('department') == 'Vendor-panel' || localStorage.getItem('department') == 'Vendor') {
    //     this.router.navigate(['/vendor-panel']);
    //   } else {
    //     alertify.error('Access Denied');
    //   }
    // }
    // else if (value == 'employee') {
    //   if (localStorage.getItem('department') == 'Employee' || localStorage.getItem('department') == 'Employee') {
    //     this.router.navigate(['/employee-dashboard']);
    //   } else {
    //     alertify.error('Access Denied');
    //   }
    // }
  }
}
