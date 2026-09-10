import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

declare let alertify: any;

interface StandardAccount {
  role: string;
  suffix: string;
  flag: string;
  emp_id: string;
  password: string;
  exists: string;
  rights: string;
}

interface StandardDept {
  department: string;
  accounts: StandardAccount[];
}

@Component({
  selector: 'app-user-accounts',
  templateUrl: './accounts.component.html',
  styleUrls: ['./accounts.component.css'],
})
export class AccountsComponent implements OnInit {
  employees: any[] = [];
  loading = false;
  searchText = '';

  showStandard = false;
  standardLoading = false;
  standardMatrix: StandardDept[] = [];

  defaultPassword = 'Med@2026';

  constructor(private service: DataAccessService) {}

  ngOnInit(): void {
    this.getAccounts();
    this.getStandardMatrix();
  }

  logout(): void {
    this.showStandard = false;
    this.employees = [];
    sessionStorage.removeItem('accounts_admin_authed');
    localStorage.removeItem('login');
    // Reset the application URL path to root before logging out
    window.location.hash = '#/';
    if (this.service && typeof this.service.logout === 'function') {
      this.service.logout();
    } else {
      window.location.reload();
    }
  }

  get plantId(): string {
    return localStorage.getItem('plant_id') || '';
  }

  get filteredEmployees(): any[] {
    if (!Array.isArray(this.employees)) {
      return [];
    }
    const term = (this.searchText || '').toLowerCase().trim();
    if (!term) {
      return this.employees;
    }
    return this.employees.filter((e) => {
      const name = this.fullName(e).toLowerCase();
      const empId = (e.emp_id || '').toString().toLowerCase();
      const dept = (e.department || '').toLowerCase();
      return name.includes(term) || empId.includes(term) || dept.includes(term);
    });
  }

  fullName(e: any): string {
    return [e?.firstname, e?.middlename, e?.lastname].filter(Boolean).join(' ').trim();
  }

  getAccounts(): void {
    this.loading = true;
    this.service.get('hr/employee.php?type=getAllUserAccounts').subscribe(
      (response: any) => {
        this.employees = Array.isArray(response) ? response : [];
        this.loading = false;
      },
      () => {
        this.employees = [];
        this.loading = false;
        if (typeof alertify !== 'undefined') {
          alertify.error('Failed to load employees. Please try again!');
        }
      }
    );
  }

  openStandard(): void {
    this.showStandard = true;
    this.getStandardMatrix();
  }

  closeStandard(): void {
    this.showStandard = false;
  }

  getStandardMatrix(): void {
    this.standardLoading = true;
    this.service.get('hr/employee.php?type=getStandardLoginMatrix').subscribe(
      (response: any) => {
        this.standardMatrix = Array.isArray(response) ? response : [];
        this.standardLoading = false;
      },
      () => {
        this.standardMatrix = [];
        this.standardLoading = false;
        if (typeof alertify !== 'undefined') {
          alertify.error('Failed to load standard login matrix.');
        }
      }
    );
  }

  createStandard(dept: StandardDept, account: StandardAccount): void {
    const payload = {
      department: dept.department,
      role_label: account.role,
      role_suffix: account.suffix,
      role_flag: account.flag,
    };
    this.service
      .post('hr/employee.php?type=createStandardLogin', JSON.stringify(payload))
      .subscribe(
        (response: any) => {
          if (response && response['status'] === 'success') {
            account.exists = 'Yes';
            account.rights = 'Yes';
            if (typeof alertify !== 'undefined') {
              alertify.success('Created ' + response['emp_id'] + ' (pass: ' + response['password'] + ')');
            }
            this.getAccounts();
          } else {
            if (typeof alertify !== 'undefined') {
              alertify.error('Failed: ' + (response && response['message'] ? response['message'] : 'try again'));
            }
          }
        },
        () => {
          if (typeof alertify !== 'undefined') {
            alertify.error('Failed to create standard login.');
          }
        }
      );
  }

  createAll(): void {
    const pending: { dept: StandardDept; acc: StandardAccount }[] = [];
    this.standardMatrix.forEach((dept) => {
      dept.accounts.forEach((acc) => {
        if (acc.exists !== 'Yes' || acc.rights !== 'Yes') {
          pending.push({ dept, acc });
        }
      });
    });
    if (pending.length === 0) {
      if (typeof alertify !== 'undefined') {
        alertify.message('All standard logins already exist.');
      }
      return;
    }
    pending.forEach((p) => this.createStandard(p.dept, p.acc));
  }

}
