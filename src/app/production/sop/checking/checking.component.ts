import { HttpClient } from '@angular/common/http';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-checking',
  templateUrl: './checking.component.html',
  styleUrls: ['./checking.component.css']
})
export class CheckingComponent implements OnInit {
  
  isView = false;
  results;

  selectedResult = [];
  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getpendinginitiation();
  }

  getpendinginitiation() {
    this.service.get('sops.php?type=getPendingSOPs').subscribe(response => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedResult = this.results[index];
    this.isView = true;
  }

  update(status) {
    this.service.get('sops.php?type=checkCreatedSOP&status=' + status + '&sop_no=' + this.selectedResult['sop_no']).subscribe(response => {
      if (response['status'] == 'success') {
        alert('Updated successfully');
        this.isView = false;
        this.getpendinginitiation();
      } else {
        alert('Failed: AN error occured');
      }
    });
  }

}
