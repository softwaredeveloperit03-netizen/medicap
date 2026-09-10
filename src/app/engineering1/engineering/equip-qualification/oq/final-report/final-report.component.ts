import { Component } from '@angular/core';

 
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';
declare let alertify; 
@Component({
  selector: 'app-final-report',
  templateUrl: './final-report.component.html',
  styleUrls: ['./final-report.component.css']
})



 
export class FinalReportComponent {
  CheckList: any[] = []; // Stores functional check records

  constructor() {}

  // Function to add new data to the checklist
  addData(reviewForm) {
    if (!reviewForm.valid) {
      alert('All fields are required!');
      return;
    }

    const newEntry = {
      Functional_check: reviewForm.value.Functional_check,
      Expected_result: reviewForm.value.Expected_result,
      Acceptance_criteria: reviewForm.value.Acceptance_criteria,
      Acceptable: reviewForm.value.Acceptable,
      Performed_by: reviewForm.value.Performed_by,
      date: reviewForm.value.date
    };

    this.CheckList.push(newEntry); // Add new entry
    reviewForm.resetForm(); // Clear form fields after submission
  }

  // Function to delete an entry from the checklist
  delData(index: number) {
    this.CheckList.splice(index, 1);
  }

  // Function to save data (this can be replaced with an API call)
  save(reviewForm) {
    if (this.CheckList.length === 0) {
      alert('No records to save!');
      return;
    }
    
    console.log('Saved Data:', this.CheckList);
    alert('Checklist saved successfully!');
    reviewForm.resetForm(); // Reset form
    this.CheckList = []; // Clear the list after saving
  }

  // Function to download checklist data as a JSON file
  download() {
    if (this.CheckList.length === 0) {
      alert('No data available for download!');
      return;
    }

    const dataStr = 'data:text/json;charset=utf-8,' + encodeURIComponent(JSON.stringify(this.CheckList, null, 2));
    const downloadAnchor = document.createElement('a');
    downloadAnchor.setAttribute('href', dataStr);
    downloadAnchor.setAttribute('download', 'checklist_data.json');
    document.body.appendChild(downloadAnchor);
    downloadAnchor.click();
    document.body.removeChild(downloadAnchor);
  }
}




// export class FinalReportComponent implements OnInit {
   

//   constructor(private service: DataAccessService, private router: Router) { }
//   CheckList: any[] = [];
   
//   ngOnInit(): void {
//   }
  
//   addData(data) {
//     if (!data.valid) {
//       alertify.error('All fields are required!');
//       return;
//     }
//     let temp = data.value;
//     this.CheckList[this.CheckList.length] = temp;
//     data.resetForm();
//   }

  

//   delData(index){
//     this.CheckList.splice(index,1);
//   }
//   download(){
  
//       this.service.open('');
     
   
  
  
//   }
   
 
// }