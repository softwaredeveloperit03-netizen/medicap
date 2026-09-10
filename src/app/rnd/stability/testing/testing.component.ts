import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-testing',
  templateUrl: './testing.component.html',
  styleUrls: ['./testing.component.css']
})
export class TestingComponent implements OnInit {

  testings;
  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getStabilityTestingReport();
  }

  getStabilityTestingReport() {
    this.service.get('stability.php?type=getStabilityTestingReport').subscribe(response => {
      this.testings = response;
    });
  }

}
