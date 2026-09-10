import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-training-attendance',
  templateUrl: './training-attendance.component.html',
  styleUrls: ['./training-attendance.component.css']
})
export class TrainingAttendanceComponent implements OnInit {
  ngOnInit(): void {

  }

  trainingData:any;
}
