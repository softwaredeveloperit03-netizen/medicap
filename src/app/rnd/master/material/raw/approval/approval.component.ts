import { Component, OnInit } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
import { DeptNavigationService } from 'src/app/shared/dept-navigation.service';
declare let alertify;

@Component({
  selector: 'app-approval',
  templateUrl: './approval.component.html',
  styleUrls: ['./approval.component.css']
})
export class ApprovalComponent implements OnInit {

  loggedInDept = localStorage.getItem('department');
  plant_id = this.service.getPlantConfigFields('plant_id');
  plant_type = this.service.getPlantConfigFields('plant_type');
  closeRoute = '/rnd';
  loading = false;
  results: any[] = [];

  constructor(
    private service: DataAccessService,
    private router: Router,
    private route: ActivatedRoute,
    private deptNav: DeptNavigationService
  ) {
    this.loggedInDept = localStorage.getItem('department');
    this.plant_id = this.service.getPlantConfigFields('plant_id');
    this.plant_type = this.service.getPlantConfigFields('plant_type');
  }

  ngOnInit(): void {
    const dept = (this.loggedInDept || '').trim().toLowerCase();
    const returnUrl = this.route.snapshot.queryParamMap.get('returnUrl');
    if (returnUrl) {
      this.closeRoute = returnUrl;
    } else if (dept === 'npd' || this.router.url.includes('npdApproval')) {
      this.closeRoute = '/npd';
    }
    this.getMaterialsLog();
  }

  material_type = 'Raw Material';

  getMaterialsLog() {
    this.loading = true;
    this.service
      .get('master/rnd_material.php?type=getMaterialsForApproval&material_type=' + encodeURIComponent(this.material_type))
      .subscribe({
        next: (response) => {
          this.results = Array.isArray(response) ? response : [];
          this.loading = false;
        },
        error: () => {
          this.results = [];
          this.loading = false;
          alertify.error('Unable to load materials for approval');
        },
      });
  }

  selectedResult = [];
  isView = false;
   
  view(data) {
    this.selectedResult = data
    this.isView = true;
  }


  viewMsds(url) {
    url = this.service.url + '../../upload/material/' + url;
    window.open(url, '_blank');
  }

  ApproveMaterial(){

    let temp ={};
 
    this.service.post('master/rnd_material.php?type=approveMaterial&id=' + this.selectedResult['id'] , JSON.stringify(temp)).subscribe((response) => {
      if (response['status'] == 'success') {
        alertify.success('Material Approved Successfully');
        this.isView = false;
        this.getMaterialsLog();
      } else {
        alertify.error(response['status']);
      }
    });

  }





  searchQuery;

  get filteredMaterials(): any[] {
    const rows = Array.isArray(this.results) ? this.results : [];
    if (!this.searchQuery || this.searchQuery.trim() === '') {
      return rows;
    }

    const query = this.searchQuery.toLowerCase().trim();

    return rows.filter((material) => {
      // Check if any field of the material contains the search query
      return Object.entries(material).some(([key, value]) => {
        if (key === 'entry_date') {
          // Convert the value to a Date object if it's not already
          const dateValue = typeof value === 'string' ? new Date(value) : value;
          // Check if the date value is valid and includes the search query
          return (
            dateValue instanceof Date &&
            dateValue.toISOString().slice(0, 10).includes(query)
          );
        } else {
          // Convert field value to lowercase and check if it includes the search query
          return value && value.toString().toLowerCase().includes(query);
        }
      });
    });
  }



  closePage(): void {
    this.deptNav.goBack(this.route, this.closeRoute);
  }

}
