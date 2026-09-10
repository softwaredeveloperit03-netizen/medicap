import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-indend-approval',
  templateUrl: './indend-approval.component.html',
  styleUrls: ['./indend-approval.component.css']
})
export class IndendApprovalComponent implements OnInit {
  indends;
  selectedIndend: any;
  isShow = false;
  materials: any;

  isReject = false;
  indend_no = '';
  indend_no1 = '';
  reason;

  generalIndends;
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getPendingIndends();

    this.getPendingGeneralIndends();
  }

  getPendingIndends() {
    this.service.get('purchase.php?type=getPendingIndends').subscribe(response => {
      this.indends = response;
    });
  }

  getPendingGeneralIndends() {
    this.service.get('purchase.php?type=getPendingGeneralIndends').subscribe(response => {
      this.generalIndends = response;
    });
  }

  selectIndend(index) {
    this.selectedIndend = this.indends[index];
    this.materials = this.selectedIndend['materials'];
    this.isShow = true;
  }

  actionIndend(action, index) {
    this.service.post('purchase.php?type=actionIndend&action=' + action, JSON.stringify(this.materials[index])).subscribe(response => {
      this.getPendingIndendMaterials();
    });
  }

  updateGeneralIndend(indend, action) {
    this.service.get('purchase.php?type=updateGeneralIndend&indend_no=' + indend + '&action=' + action).subscribe(response => {
      if (response['status'] == "success") {
        alert('Updated successfully');
        this.getPendingGeneralIndends();
      }
    });
  }

  showReject(value, indend) {
    this.indend_no1 = indend;
    this.indend_no = value;
    this.isReject = true;
  }

  getPendingIndendMaterials() {
    this.service.get('purchase.php?type=getPendingIndendMaterials&indend_no=' + this.indend_no1).subscribe(response => {
      this.materials = response;
    });
  }

  closeShow() {
    this.isShow = false;
    this.getPendingIndends();
  }

  updateQty(index, qty) {
    this.materials[index].required_qty = qty;
  }
}
