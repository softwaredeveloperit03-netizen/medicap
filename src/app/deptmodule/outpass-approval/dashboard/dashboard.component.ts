import { DataAccessService } from 'src/app/data-access.service';
import { Component, OnInit } from '@angular/core';
import { DatePipe } from '@angular/common';
declare let alertify;

@Component({
  selector: 'app-plant-outpass-approval-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css'],
  providers: [DatePipe]
})
export class PlantOutpassApprovalDashboardComponent implements OnInit {

  results: any[] = [];
  searchQuery = '';
  plant_head = 'No';

  constructor(private service: DataAccessService, private datepipe: DatePipe) {}

  ngOnInit() {
    this.get_rights();
    this.getOutpassForPlantHead();
  }

  get_rights() {
    this.service.get('hr/employee.php?type=getrights&emp_id=' + localStorage.getItem('emp_id') + '&dep_name=' + localStorage.getItem('department')).subscribe((res: any) => {
      this.plant_head = res?.[0]?.plant_head || 'No';
    });
  }

  getOutpassForPlantHead() {
    this.service.get('security/outpass_api.php?type=getOutpassForPlantHead').subscribe((response: any) => {
      this.results = Array.isArray(response) ? response : [];
    });
  }

  get filteredResults(): any[] {
    if (!this.results || this.results.length === 0) return [];
    if (!this.searchQuery || this.searchQuery.trim() === '') return this.results;
    const q = this.searchQuery.toLowerCase().trim();
    return this.results.filter(r =>
      Object.values(r).some(v => v && String(v).toLowerCase().includes(q))
    );
  }

  approve(id: string | number) {
    if (id == null) return;
    this.service.get('security/outpass_api.php?type=plantHeadApproveOutpass&id=' + encodeURIComponent(String(id)) + '&action=approve').subscribe((res: any) => {
      if (res && res.status === 'success') {
        alertify.success('Approved');
        this.getOutpassForPlantHead();
      } else {
        alertify.error(res?.message || 'Failed');
      }
    });
  }

  reject(id: string | number) {
    if (id == null) return;
    this.service.get('security/outpass_api.php?type=plantHeadApproveOutpass&id=' + encodeURIComponent(String(id)) + '&action=reject').subscribe((res: any) => {
      if (res && res.status === 'success') {
        alertify.success('Rejected');
        this.getOutpassForPlantHead();
      } else {
        alertify.error(res?.message || 'Failed');
      }
    });
  }
}
