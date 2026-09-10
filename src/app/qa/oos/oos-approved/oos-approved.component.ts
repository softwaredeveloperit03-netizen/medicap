import { Component, OnInit } from '@angular/core';
import { FormBuilder } from '@angular/forms';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-oos-approved',
  templateUrl: './oos-approved.component.html',
  styleUrls: ['./oos-approved.component.css']
})
export class OosApprovedComponent implements OnInit {

  isView = false;
  isNew = false;
  selectedEntry;

  Oos = [];

  list;

  constructor(private service: DataAccessService, private router: Router, private fb: FormBuilder) {

   }

  ngOnInit() {
    this.getApprovedOosData();
  }


  view(index) {
    this.selectedEntry = this.list[index];
    this.isView = true;
  }

  getApprovedOosData() {
    this.service.get('qc/oos.php?type=getCheckedOosData').subscribe(response => {
      this.list = JSON.parse(JSON.stringify(response));
    });
  }

    addVendorAgenda() {
      this.isNew = true;
    }

  close() {
    this.router.navigate(['/oos']);
  }

  updateOos(status) {
    this.service.get('qc/oos.php?type=approveOOS&status=' + status + '&id=' + this.selectedEntry['id']).subscribe(response => {
      if (response['status'] == 'success') {
        alert('OOS Updated Successfully');
        this.isNew = false;
        this.getApprovedOosData();
      } else {
        alert('Failed: An error occured, please try again!');
      }
    });
  }

}



