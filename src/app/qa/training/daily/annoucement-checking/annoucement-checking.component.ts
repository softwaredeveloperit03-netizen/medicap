import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-annoucement-checking',
  templateUrl: './annoucement-checking.component.html',
  styleUrls: ['./annoucement-checking.component.css']
})
export class AnnoucementCheckingComponent implements OnInit {

  isView = false;
  results;

  selectedResult = [];
  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getPendingDailyAnnoucements();
  }

  getPendingDailyAnnoucements() {
    this.service.get('training.php?type=getPendingDailyAnnoucements').subscribe(response => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedResult = this.results[index];
    this.isView = true;
  }

  update(status) {
    this.service.get('training.php?type=checkDailyAnnoucement&status=' + status + '&id=' + this.selectedResult['id']).subscribe(response => {
      if (response['status'] == 'success') {
        alert('Updated Successfully');
        this.isView = false;
        this.getPendingDailyAnnoucements();
      } else {
        alert('Failed: An error occured, please try again!');
      }
    });
  }

}
