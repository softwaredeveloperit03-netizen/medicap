import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Chart, LinearScale, LineController, LineElement, PointElement, registerables, Title } from 'chart.js';


declare let alertify;

@Component({
  selector: 'app-reports',
  templateUrl: './reports.component.html',
  styleUrls: ['./reports.component.css']
})
export class ReportsComponent implements OnInit {

  isView = false;
  results;
  results1;
  materials;
  selectedReport = [];
  from_date = '';
  material_name = '';
  checklist = [];
  plant_id: any;

  checkPointData: any;

  constructor(private service: DataAccessService, ) {}

  async ngOnInit() {
    this.plant_id = this.service.getPlantConfigFields('plant_id');
    await this.getPendingCheckingGRN();
    await this.getMaterials();
    await this.getCheckPointData();
   }

  /**************** Module and Form Name will be dynamic    To-do */
  getCheckPointData() {
    return new Promise((res, rej) => {
      this.service
        .get(
          'master/checklist.php?type=getCheckPointByForm&module=Grn&form=GRN Checking'
        )
        .subscribe((response) => {
          this.checkPointData = response;
          res(response);
        });
    });
  }

  getPendingCheckingGRN() {
    return new Promise((res, rej) => {
      this.service
        .get('store/raw.php?type=getPendingCheckingGRN')
        .subscribe((response) => {
          this.results = response;
          this.results1 = response;
          res(response);
        });
    });
  }

  getMaterials() {
    return new Promise((res, rej) => {
      this.service
        .get('common.php?type=getRawMaterials')
        .subscribe((response) => {
          this.materials = response;
          res(response);
        });
    });
  }

 async view(index) {
    this.selectedReport = this.results[index];
    this.isView = true;
    await this.getCheckPointData();
    console.log('this.checkPointData :>> ', this.checkPointData);
   }

  update(status) {
    let temp = this.selectedReport;

    // console.log(this.checkPointData);
    // return false ;
    // temp["id"] = this.selectedReport["id"];
    // temp["accept_qty"] = this.selectedReport["accept_qty"];
    // temp["unit"] = this.selectedReport["unit"];
    // temp['vendor_no'] = this.selectedReport["vendor_no"];
    // temp["material_code"] = this.selectedReport["material_code"];
    // temp["batch_no"] = this.selectedReport["batch_no"];
    // temp["mfg_date"] = this.selectedReport["mfg_date"];
    // temp["exp_date"] = this.selectedReport["exp_date"];
    // temp['containers'] = this.selectedReport['container_details'];
    temp['batches'] = this.selectedReport['batches'];
    temp['status'] = status;
    // temp['checklist'] = this.checklist;
    temp['checklist'] = this.checkPointData;
    // temp['challan_no'] = this.selectedReport['challan_no'];
    this.service
      .post('store/raw.php?type=updateGRN', JSON.stringify(temp))
      .subscribe((response) => {
        if (response['status'] == 'success') {
          alertify.success('GRN Updated successfully');
          this.isView = false;
          this.getPendingCheckingGRN();
        } else {
          alertify.error('Failed: An error occured, please try again!');
        }
      });
  }

  viewfile(url) {
    url = this.service.url + 'upload/coa/' + url;
    window.open(url, '_blank');
  }

  viewChallan(url) {
    url = this.service.url + 'upload/challan/' + url;
    window.open(url, '_blank');
  }

  filterStock() {
    this.results = [];
    for (let i = 0; i < this.results1.length; i++) {
      let data = this.results1[i];
      if (
        data.material_name
          .toUpperCase()
          .includes(this.material_name.toUpperCase())
      ) {
        this.results.push(data);
      }
    }
  }

  clear() {
    // this.from_date = '';
    this.material_name = '';
    this.results = this.results1;
  }
}
