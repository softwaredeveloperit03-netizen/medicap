import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-cleaning',
  templateUrl: './cleaning.component.html',
  styleUrls: ['./cleaning.component.css']
})
export class CleaningComponent implements OnInit {

  results;
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getCleaningReport();
  }

  getCleaningReport() {
    this.service.get('balance.php?type=getCleaningReport').subscribe(response => {
      this.results = response;
    });
  }

}
