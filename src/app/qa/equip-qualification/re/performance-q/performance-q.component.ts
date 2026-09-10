import { Component, OnInit } from '@angular/core';

@Component({
  selector: 'app-performance-q',
  templateUrl: './performance-q.component.html',
  styleUrls: ['./performance-q.component.css'],
})
export class PerformanceQComponent implements OnInit {
  constructor() {}

  ngOnInit(): void {}
  // isShown01 = false;
  // toggleShow01() {
  //   this.isShow01 = true;

  // }
  testinginstructiondemo: any[] = [];
  testinginstruction: any[] = [];
  testing_instruction: string = '';
  evaluation_parameter: string = '';
  check_by: string = '';

  // Function to add a new instruction

  add() {
    if (
      this.testing_instruction &&
      this.evaluation_parameter &&
      this.check_by
    ) {
      this.testinginstructiondemo.push({
        testing_instruction: this.testing_instruction,
        evaluation_parameter: this.evaluation_parameter,
        check_by: this.check_by, // Include check_by here
      });
      // Clear input fields after adding instruction
      this.testing_instruction = '';
      this.evaluation_parameter = '';
      this.check_by = '';
    }
  }

  // Function to delete an instruction
  deleteinstruction(index: number) {
    this.testinginstructiondemo.splice(index, 1);
  }
  // Function to add a new main instruction heading
  testing_heading = '';
  addMaininstruction() {
    let temp = {};
    temp['testing_heading'] = this.testing_heading;
    temp['testing_instruction'] = this.testinginstructiondemo;
    this.testinginstruction[this.testinginstruction.length] = temp;
    this.testing_heading = '';
    this.testinginstructiondemo = [];

    console.log(this.testinginstruction);
  }
  // Function to delete a main instruction heading
  deleteMaininstruction(index: number) {
    this.testinginstruction.splice(index, 1);
  }
  // Function to save instructions
  saveInstructions() {
    // Implement saving logic here
    console.log('Instructions saved!');
  }
  selectedMethod: any = [];
}
