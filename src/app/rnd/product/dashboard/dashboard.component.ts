import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify: any;

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css']
})
export class DashboardComponent implements OnInit {
  selectedResult;
  results;
  View=false
  requirements = [];
  isView = false;
  isRequirement = false;
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getProductsLog();
  }
  getProductsLog() {
    this.service.get('rnd/product.php?type=getApprovedRequirements').subscribe(response => {
      this.results = response;
    })

  }
  view(index) {
    this.selectedResult = this.results[index];
    this.View = true;
    this.isView=true

  }
  requirement(index) {
    this.selectedResult = this.results[index];
    this.isRequirement = true;
    this.View=false
    this.isView=true

  }
  addRequirement(data) {
    if (!data.valid) {
      alertify.error('Please Enter required Field');
      return;
    }
    this.requirements.push(data.value);
    data.reset();
  }
  del(index) {
    this.requirements.splice(index, 1);
  }

  saveRequirement() {
    if (this.requirements.length == 0) {
      alertify.error('Please Enter Requirements List');
      return;
    }
    let obj = {
        "id" : this.selectedResult['id'],
        "list" : this.requirements
    }
    this.service.post('rnd/product.php?type=updatRequirementList',JSON.stringify(obj)).subscribe(response => {
      if (response['status'] == 'success') {
        this.isView = false;
        this.isRequirement = false;
        this.View=false
        this.isView=false
        this.requirements=[];
        this.getProductsLog()
        alertify.success(this.service.t('common.savedSuccess'));
      } else {
        alertify.error('Failed: An error occured');
      }
    })

  }

  closeView()
  {
    this.isView=false;
    this.View=false;
    this.isRequirement=false
  }
  closeReq()
  {
    this.isView=false;
    this.View=false;
    this.isRequirement=false
  }
}
