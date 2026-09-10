import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-implementation',
  templateUrl: './implementation.component.html',
  styleUrls: ['./implementation.component.css']
})
export class ImplementationComponent implements OnInit {

  isView = false;
  results;

  selectedResult = [];
  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getPendingSOPImplementation();
  }

  getPendingSOPImplementation() {
    this.service.get('sops.php?type=getPendingSOPImplementation').subscribe(response => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedResult = this.results[index];
    this.isView = true;
  }

  save() {
    this.service.get('sops.php?type=implementSOP&id=' + this.selectedResult['id']).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('SOP Implemented Successfully');
        this.isView = false;
        this.getPendingSOPImplementation();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

}
