import { Component, OnInit } from '@angular/core';

@Component({
  selector: 'app-initiate',
  templateUrl: './initiate.component.html',
  styleUrls: ['./initiate.component.css']
})
export class InitiateComponent implements OnInit {
  selectedStability: any;
    results: any;
    isView: false;

  constructor() { }

  ngOnInit(): void {
  }
  viewProtocol(index) {
    this.selectedStability = this.results[index];
  }

}
