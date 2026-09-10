import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
 declare let alertify;


@Component({
  selector: 'app-mainantance',
  templateUrl: './mainantance.component.html',
  styleUrls: ['./mainantance.component.css']
})
export class MainantanceComponent implements OnInit {
  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getDepartmentsWithSections();
  }


  departments;
  getDepartmentsWithSections(){
    this.service.get('admin/asset.php?type=getDepartmentsWithSections').subscribe((response:any) => {
      this.departments = response;
    });
  }

  assets;


  department = '';
  location = '';


  getAssetByDepartmentAndLocaton(){
    this.service.get('admin/asset.php?type=getAssetByDepartmentAndLocaton&dept='+this.department+'&location=' + encodeURIComponent(this.location)).subscribe((response:any) => {
      this.assets = response;
    });
  }

  locations = [];
  getLoactions(dept){
    const selectedDepartment =  this.departments.find(depart => depart.department_name === dept);
    this.locations = selectedDepartment['locations']
  }


  saveAssetRecord(data){
  
    if(!data.valid){
      alertify.error("All Field Required!!!!!!!");
      return;
    }
    let temp = data.value;

    this.service.post('admin/asset.php?type=saveMaintenanceAssetRecord', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Asset Saved Successfully .....');
        this.getAssetByDepartmentAndLocaton();
        data.reset();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });

  }

  }
