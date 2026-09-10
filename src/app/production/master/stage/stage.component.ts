import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-stage',
  templateUrl: './stage.component.html',
  styleUrls: ['./stage.component.css']
})
export class StageComponent implements OnInit {

  isNew = false;
  results;

  selectedResult = [];

  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit() {
   this.getPendingStages();
  }

  getPendingStages(){
    this.service.get('production/master.php?type=getPendingStages').subscribe(response => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedResult = this.results[index];
    this.isNew = true;
  }

  addPage(id) {
    this.router.navigate(['/master/step/' + id]);
  }
  
}
