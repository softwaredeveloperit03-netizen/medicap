import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';

@Component({
  selector: 'app-self-learn-sub-dash',
  templateUrl: './self-learn-sub-dash.component.html',
  styleUrls: ['./self-learn-sub-dash.component.css']
})
export class SelfLearnSubDashComponent implements OnInit {

  constructor(private router:Router) { }

  ngOnInit(): void {
  }
  goToSelfLearning() {
    this.router.navigate(['/qa/training/self-learning']);
  }

  goToAllTrainingType() {
    this.router.navigate(['/qa/training/all-training-type']);
  }
}
