import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';
declare let alertify;

@Component({
  selector: 'app-upload-report',
  templateUrl: './upload-report.component.html',
  styleUrls: ['./upload-report.component.css']
})
export class UploadReportComponent implements OnInit {

  results;
  isView=false;
  phisicians;
  doctor_name='';
  
  doctor='';
  selectedReport=[];
  data = [];
  selectedFile: File;
  // selectedTest: any = [];
  selectedTest = {
    tests: [] // Initialize your selectedTest data structure
  };
  // router: any;
  constructor(private service:DataAccessService, private router: Router) { }

  ngOnInit() {
    this.getPremedicalsLog();
    this.getApprovedPhisicians();
  }
  //----------------------For Pagination---------------------------------//

  currentPage: number = 1;
  pageSize: number = 10; // Default page size

  calculateStartSrNo(): number {
    return (this.currentPage - 1) * 10 ;
  }
  
  onPageChange(page: number) {
    this.currentPage = page;
    console.log(this.currentPage);
  }
  
  onPageSizeChange(event: any) {
    this.pageSize = parseInt(event.target.value, 10); // Parse the selected value to an integer
  }
  viewf(){
    this.isView=false;
    //  this.getLogs();
    this.currentPage=1;
    this.pageSize =10;
    
  }
  // ---------------------------------------------------------------------//
  // onFileChanged(event) {
  //   this.selectedFile = event.target.files[0];
  // }
  onFileChanged(event, data) {
    data.file = event.target.files[0]; // Associate the selected file with the data object
  }
  getPremedicalsLog(){
    this.service.get('hr/medical.php?type=getPremedicalsLog').subscribe((response:any) =>{
      this.results=response;
     });
  }
  getApprovedPhisicians() {
    this.service.get('hr/physician.php?type=getPhysicians')
    .subscribe(response => {
      this.phisicians = response;
    });
  }
  selectedReportdata=[];
  test_name;




  searchQuery;

  get filteredMaterials(): any[] {
    if (!this.searchQuery || this.searchQuery.trim() === '') {
      return this.results; // If search query is empty or whitespace, return all materials
    }

    const query = this.searchQuery.toLowerCase().trim(); // Convert search query to lowercase and trim whitespace

    return this.results.filter((material) => {
      // Check if any field of the material contains the search query
      return Object.entries(material).some(([key, value]) => {
        if (key === 'entry_date') {
          // Convert the value to a Date object if it's not already
          const dateValue = typeof value === 'string' ? new Date(value) : value;
          // Check if the date value is valid and includes the search query
          return (
            dateValue instanceof Date &&
            dateValue.toISOString().slice(0, 10).includes(query)
          );
        } else {
          // Convert field value to lowercase and check if it includes the search query
          return value && value.toString().toLowerCase().includes(query);
        }
      });
    });
  }





  view(index) {
    this.selectedReport = this.filteredMaterials[index];
    
    this.isView = true;
    if (this.selectedReport && this.selectedReport['tests']) {
      this.selectedTest = this.selectedReport['tests'];
      
      if (this.selectedTest && this.selectedTest['tests'] && this.selectedTest['tests'].length > 0) {
        this.selectedReportdata = this.selectedTest['tests'];
        this.test_name = this.selectedReportdata[0]['test'];
        console.log(this.test_name);
      } else {
        console.error('Selected test data is undefined or empty.');
      }
    } else {
      console.error('Selected report or tests property is undefined.');
    }
  }
  
 
save(data) {
  const uploadData = new FormData();

  // Iterate through the data points and add each file to FormData
  this.selectedTest.tests.forEach((data, index) => {
    if (data.file) {
      uploadData.append(`test_${data.id}`, data.file, data.file.name);
    }
  });
console.log(uploadData);
 

this.service.post('hr/medical.php?type=saveReport&id='+this.selectedReport['emp_id'], uploadData).subscribe(
  (response) => {
    if (response['status'] === 'success') {
      alertify.success('Record Inserted Successfully');
      this.isView=false;
      this.getPremedicalsLog();
  

    } else {
      alertify.error(response['status']);
      alertify.error('Failed: An error occurred, please try again!');
    }
  },
  (error) => {
    console.error('Error uploading files:', error);
   }
);

}

}
