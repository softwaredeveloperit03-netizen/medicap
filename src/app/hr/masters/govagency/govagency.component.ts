import { DataAccessService } from 'src/app/data-access.service';
import { Component, OnInit } from '@angular/core';
declare let alertify;


@Component({
  selector: 'app-govagency',
  templateUrl: './govagency.component.html',
  styleUrls: ['./govagency.component.css']
})
export class GovagencyComponent implements OnInit {
  govagencies;
  qualifications;
  isNewAgency = false;
  result;
  selectedResult=[];
  isView=false;
  constructor(private service: DataAccessService) {
    this.loggedInDept = localStorage.getItem('department');

   }

  ngOnInit() {
    this.getGovagencys();
    this.get_rights();
  }
  
  isuser = 'No';
  ischecker = 'No';
  isapprover = 'No';
  qms_approver = 'No';
  dept_head = 'No';
  isauditor = 'No';
  plant_head = 'No';
  shift_allocator = 'No';
  rights;
  loggedInDept;

  get_rights() {this.service.get('hr/employee.php?type=getrights&emp_id=' 
    +localStorage.getItem('emp_id') +'&dep_name=' +this.loggedInDept     
       )
      .subscribe((response) => {
        this.rights = response;
        this.isuser = this.rights[0].isuser;
        this.ischecker = this.rights[0].ischecker;
        this.isapprover = this.rights[0].isapprover;
        this.qms_approver = this.rights[0].qms_approver;
        this.dept_head = this.rights[0].dept_head;
        this.isauditor = this.rights[0].isauditor;
        this.plant_head = this.rights[0].plant_head;
        this.shift_allocator = this.rights[0].shift_allocator;
      });
  }
  view(index) {
    this.selectedResult = this.govagencies[index];
    console.log(this.selectedResult)
    this.isView = true;
  }

  getGovagencys() {
    this.service.get('hrDepartment.php?type=getGovagencys')
    .subscribe(response => {
      this.govagencies = response;
    });
  }
 

  addGovagency(govAgencyForm) {

    if (!govAgencyForm.valid) {
      alertify.error('All fields are required');
      return;
    } 

    this.isNewAgency = false;
    this.service.post('hrDepartment.php?type=addGovagency', JSON.stringify(govAgencyForm.value))
    .subscribe(response => {
      govAgencyForm.reset();
      this.getGovagencys();
      },
    (error: Response) => {
      if (error.status === 400) {
        alert('An error has occurred.');
      } else {
        alert('An error has occurred, http status:' + error.status);
      }
    });
  }

  
  searchQuery;


  get filteredMaterials(): any[] {
    if (!this.searchQuery || this.searchQuery.trim() === '') {
      return this.govagencies; // If search query is empty or whitespace, return all materials
    }
    
    const query = this.searchQuery.toLowerCase().trim(); // Convert search query to lowercase and trim whitespace
  
    return this.govagencies.filter(material => {
      // Check if any field of the material contains the search query
      return Object.entries(material).some(([key, value]) => {
        if (key === 'entry_date') {
          // Convert the value to a Date object if it's not already
          const dateValue = typeof value === 'string' ? new Date(value) : value;
          // Check if the date value is valid and includes the search query
          return dateValue instanceof Date && dateValue.toISOString().slice(0, 10).includes(query);
        } else {
          // Convert field value to lowercase and check if it includes the search query
          return value && value.toString().toLowerCase().includes(query);
        }
      });
    });
  }
}
