import { Component, OnInit } from '@angular/core';
import { NgForm } from '@angular/forms';

@Component({
  selector: 'app-water',
  templateUrl: './water.component.html',
  styleUrls: ['./water.component.css'],
})
export class WaterComponent implements OnInit {
  data: '';
  constructor() {}

  ngOnInit(): void {}
  add(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    let temp = data.value;
    data.resetForm(); 
  }
}
