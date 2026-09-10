import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-reject',
  templateUrl: './reject.component.html',
  styleUrls: ['./reject.component.css']
})
export class RejectComponent implements OnInit {

  
  isView = false;
  vendor_no;
  materials;
  results;
  results1;

  selectedReport = [];

 
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getRejectedGrn();
  }


  getRejectedGrn() {
    this.service.get('store/raw.php?type=getRejectedGrn').subscribe(response => {
      this.results = response;
      this.results1 = response;
    });
  }

  view(index) {
    this.selectedReport = this.results[index];
    this.isView = true;
  }

  update(status) {
    let temp = this.selectedReport;
    temp["id"] = this.selectedReport["id"];
    temp["accept_qty"] = this.selectedReport["accept_qty"];
    temp["unit"] = this.selectedReport["unit"];
    temp['vendor_no'] = this.selectedReport["vendor_no"];
    temp["material_code"] = this.selectedReport["material_code"];
    temp["batch_no"] = this.selectedReport["batch_no"];
    temp["mfg_date"] = this.selectedReport["mfg_date"];
    temp["exp_date"] = this.selectedReport["exp_date"]; 
    temp['containers'] = this.selectedReport['container_details'];
    temp['urgency'] = this.selectedReport['urgency'];
    temp['status'] = status;
    temp['challan_no'] = this.selectedReport['challan_no'];
    this.service.post('store/raw.php?type=updateGRN', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('GRN Updated successfully');
        this.isView = false;
        this.getRejectedGrn();
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

  
  getMaterials() {
    this.service.get('common.php?type=getRawMaterials').subscribe(response => {
      this.materials = response;
    })
  }

  filterStock(){
    this.results = [];
    for(let i=0; i<this.results1.length; i++){
      let data = this.results1[i];
      if(data.material_name.toUpperCase().includes(this.vendor_no.toUpperCase()) ){
        this.results.push(data);
      }
    }
  }


  clear(){
    this.vendor_no = '';
    this.results = this.results1;
  }


}
