import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {

  isNew = false;
  results;
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getSamplingRecords();
  }

  getSamplingRecords() {
    this.service.get('qc/sampling/finish.php?type=getPendingSamplings').subscribe(response => {
      this.results = response;
    });
  }

}
