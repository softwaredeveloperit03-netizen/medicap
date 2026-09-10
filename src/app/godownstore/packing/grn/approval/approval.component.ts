import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-approval',
  templateUrl: './approval.component.html',
  styleUrls: ['./approval.component.css']
})
export class ApprovalComponent implements OnInit {

  isView = false;
  results;
  remark;
  selectedReport = [];

  sections;
  racks;

  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getPendingCheckingGRN();
  }

  getPendingCheckingGRN() {
    this.service.get('store/packing.php?type=getPendingCheckingGRN').subscribe(response => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedReport = this.results[index];
    this.isView = true;
    this.getSections();
  }

  getSections() {
    this.service.get('store/location.php?type=getSectionRacks').subscribe(response => {
      this.sections = response;
    });
  }

  getRacks(index) {
    index = index - 1;
    if (index !== -1) {
      this.racks = this.sections[index].racks;
    } else {
      this.racks = [];
    }
  }

  update(status) {
    let temp = {};
    temp["id"] = this.selectedReport["id"];
    temp["unit"] = this.selectedReport["unit"];
    temp['vendor_no'] = this.selectedReport["vendor_no"];
    temp["material_code"] = this.selectedReport["material_code"];
    temp["grn_no"] = this.selectedReport["grn_no"];
    temp["grn_date"] = this.selectedReport["grn_date"];
    temp["received_qty"] = this.selectedReport["received_qty"]; 
    temp["artwork_no"] = this.selectedReport["artwork_no"]; 
    temp["version_no"] = this.selectedReport["version_no"]; 
    temp["remark"] = this.remark;
    temp['status'] = status;
    this.service.post('store/packing.php?type=updateGRN', JSON.stringify(temp)).subscribe(response => {
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
