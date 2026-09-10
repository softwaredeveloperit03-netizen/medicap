import { Component, OnInit } from '@angular/core';3
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css']
})
export class DashboardComponent implements OnInit {
  isNew = false;
  finalResults:any;
  invoiceResults:any
  constructor(private service: DataAccessService) {
    this.loggedInDept = localStorage.getItem('department');

  }
  ngOnInit(): void 
  {
    this.getDataLoggerMaster()
    this.getPendingInvoices()
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


  results = [
    {
      date: '2024-06-04',
      tax_invoice_number: 'INV-001',
      vehicle_number: 'ABC-1234',
      destination: 'New York',
      data_logger_number: 'DL-001',
      placed_by: 'John Doe',
      timing_of_insertion: '10:00 AM',
      vehicle_start_time: '10:30 AM',
      operation_condition_confirm: 'Confirmed'
    },
    {
      date: '2024-06-05',
      tax_invoice_number: 'INV-002',
      vehicle_number: 'XYZ-5678',
      destination: 'Los Angeles',
      data_logger_number: 'DL-002',
      placed_by: 'Jane Smith',
      timing_of_insertion: '11:00 AM',
      vehicle_start_time: '11:30 AM',
      operation_condition_confirm: 'Confirmed'
    },
  ];


  getDataLoggerMaster() {
    this.service.get('dispatch.php?type=getDataLoggerMaster').subscribe(response => {
      this.finalResults =  response;
    });
  }

  getPendingInvoices(){
    this.service.get('dispatch/invoice.php?type=getPendingInvoices').subscribe(response=>{
      this.invoiceResults=response;
     
    });
  }

  save(data) {
    console.log('data',data);
    if (!data.valid) {
      alertify.error('All feilds are required');
      return;
    }
    let temp = data.value;
    this.service
      .post(
        'dispatch.php?type=saveDataLoggerMaster',
        JSON.stringify(temp)
      )
      .subscribe((response) => {
        if (response['status'] == 'success') {
          alert('Saved Successfully');
          this.isNew = false;
          data.reset();
          this.getDataLoggerMaster()
        } else {
          alert('Failed: An error occured, please try again!');
        }
      });
  }

  deleteData(id: any) {
    this.service.get('dispatch.php?type=deleteDataLoggerMaster&ID='+id).subscribe(response => {
      if (response['status'] == 'success') {
        alert('Deleted Successfully');
        this.getDataLoggerMaster()
      } else {
        alert('Failed: An error occured, please try again!');
      }
    });

  }
}
