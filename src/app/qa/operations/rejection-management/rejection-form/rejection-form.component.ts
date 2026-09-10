import { Component, OnInit } from '@angular/core';

@Component({
  selector: 'app-rejection-form',
  templateUrl: './rejection-form.component.html',
  styleUrls: ['./rejection-form.component.css']
})
export class RejectionFormComponent implements OnInit {

  selectedReport = [];
  isReason = true;
  constructor() { }

  ngOnInit(): void {
  }

  checkRejectionArea(value) {}

}
