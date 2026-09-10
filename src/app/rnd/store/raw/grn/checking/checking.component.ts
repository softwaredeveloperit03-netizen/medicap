import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-checking',
  templateUrl: './checking.component.html',
  styleUrls: ['./checking.component.css']
})
export class CheckingComponent implements OnInit {

  isView = false;
  results;

  selectedReport = [];

 
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getPendingCheckingGRN();
  }

  getPendingCheckingGRN() {
    this.service.get('store/raw.php?type=getPendingCheckingGRN').subscribe(response => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedReport = this.results[index];
    this.isView = true;
  }

  update(status) {
    let temp = {};
    temp["id"] = this.selectedReport["id"];
    temp["accept_qty"] = this.selectedReport["accept_qty"];
    temp["unit"] = this.selectedReport["unit"];
    temp['vendor_no'] = this.selectedReport["vendor_no"];
    temp["material_code"] = this.selectedReport["material_code"];
    temp["batch_no"] = this.selectedReport["batch_no"];
    temp["mfg_date"] = this.selectedReport["mfg_date"];
    temp["exp_date"] = this.selectedReport["exp_date"]; 
    temp['containers'] = this.selectedReport['container_details'];
    temp['status'] = status;
   

    this.service.post('store/raw.php?type=updateGRN', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('GRN Updated successfully');
        this.isView = false;
        this.getPendingCheckingGRN();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });

  }

}
