import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-sampling',
  templateUrl: './sampling.component.html',
  styleUrls: ['./sampling.component.css']
})
export class SamplingComponent implements OnInit {

  results;
  selectedStability = [];
  isView = false;
  constructor(public service: DataAccessService , private router:Router) { }

  ngOnInit(): void {
    this.getStabilities();
  }

  onClose(){
    this.router.navigate(['/rnd/qa/stability']);
  }
  getStabilities() {
    this.service.get('stability.php?type=getPendingStabilitySampling').subscribe(response => {
      this.results = response;
    });
  }

  viewProtocol(index) {
    this.selectedStability = this.results[index];
    this.isView = true;
  }

}
