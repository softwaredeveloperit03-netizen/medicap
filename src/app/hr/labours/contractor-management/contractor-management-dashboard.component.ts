import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

interface ContractorTile {
  id: string;
  title: string;
  route: string;
  icon: string;
  gradient: string;
  showWhen?: 'isuser';
}

@Component({
  selector: 'app-contractor-management-dashboard',
  templateUrl: './contractor-management-dashboard.component.html',
  styleUrls: ['./contractor-management-dashboard.component.css']
})
export class ContractorManagementDashboardComponent implements OnInit {
  isuser = 'No';
  loggedInDept: string | null = null;

  readonly tiles: ContractorTile[] = [
    { id: 'labour-contractor', title: 'Reg. for Contractor', route: '/hr/labours/labour-contractor', icon: 'fa-hard-hat', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)', showWhen: 'isuser' },
    { id: 'bill-contractor', title: 'Bills from Contractor', route: '/hr/labours/bill-contractor', icon: 'fa-file-invoice-dollar', gradient: 'linear-gradient(135deg, #30cfd0 0%, #330867 100%)' },
    { id: 'contractor-agreement', title: 'Contractor Agreement', route: '/hr/labours/contractor-agreement', icon: 'fa-file-signature', gradient: 'linear-gradient(135deg, #fa709a 0%, #fee140 100%)' }
  ];

  constructor(private service: DataAccessService) {
    this.loggedInDept = localStorage.getItem('department');
  }

  ngOnInit(): void {
    this.get_rights();
  }

  get_rights(): void {
    const empId = localStorage.getItem('emp_id');
    if (!empId) { return; }
    this.service.get(
      'hr/employee.php?type=getrights&emp_id=' +
      encodeURIComponent(empId) +
      '&dep_name=' +
      encodeURIComponent(this.loggedInDept || '')
    ).subscribe((response: any) => {
      const r = Array.isArray(response) && response[0] ? response[0] : {};
      this.isuser = r.isuser || 'No';
    });
  }

  getVisibleTiles(): ContractorTile[] {
    return this.tiles.filter(t => !t.showWhen || (t.showWhen === 'isuser' && this.isuser === 'Yes'));
  }
}
