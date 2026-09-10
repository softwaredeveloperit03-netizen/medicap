import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
 declare let alertify;

@Component({
  selector: 'app-new-asset',
  templateUrl: './new-asset.component.html',
  styleUrls: ['./new-asset.component.css']
})
export class NewAssetComponent implements OnInit {

  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getDepartmentsWithSections();
    this.getAssetsMAster();
  }


  departments;
  getDepartmentsWithSections(){
    this.service.get('admin/asset.php?type=getDepartmentsWithSections').subscribe((response:any) => {
      this.departments = response;
    });
  }

  assets;
  getAssetsMAster(){
    this.service.get('admin/asset.php?type=getAssetsMAster').subscribe((response:any) => {
      this.assets = response;
    });
  }
  
  locations = [];
  getLoactions(dept){
    const selectedDepartment =  this.departments.find(depart => depart.department_name === dept);
    this.locations = selectedDepartment['locations']
  }


  isAddAsset = false;
  checkAssest(value){
    if(value == 'ADD NEW'){
      this.isAddAsset = true;
      this.assetNo = '';
    }
  }


  assetNo = '';

  saveAssetMaster(data){
  
    if(!data.valid){
      alertify.error("All Field Required!!!!!!!");
      return;
    }
    let temp = data.value;

    this.service.post('admin/asset.php?type=saveAssetMaster', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success(response['msg'] );
        this.getAssetsMAster();
        data.reset();
        this.isAddAsset = false;
        this.assetNo = '';
      } else {
        alertify.error(response['msg'] );
      }
    });

  }


  saveAssetRecord(data){
  
    if(!data.valid){
      alertify.error("All Field Required!!!!!!!");
      return;
    }
    let temp = data.value;

    this.service.post('admin/asset.php?type=saveAssetRecord', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Asset Saved Successfully .....');
        this.getAssetsMAster();
        data.reset();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });

  }


 




}
