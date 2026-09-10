import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
import * as XLSX from 'xlsx';
@Component({
  selector: 'app-apprisalrequest',
  templateUrl: './apprisalrequest.component.html',
  styleUrls: ['./apprisalrequest.component.css']
})
export class ApprisalrequestComponent implements OnInit {

  
  isView = false;
   results;
   department;
     constructor(private service:DataAccessService) { }
  
    
    ngOnInit(): void {
      this.department = localStorage.getItem('department');
      this.getapprisals_log()
     }
  
  
    getapprisals_log() {
        this.service.get('hr/appraisalchecklist.php?type=getapprisals_log_for_HR&department1='+localStorage.getItem('department')).subscribe((response: any) => {
        this.results = response;
      
      });
    }
 
    

    
    selectedResult=[];
 
    view(index)
    {
      this.selectedResult=this.results[index];
  
      this.isView =  true ;
  
    }
    updateData(status){
      
      this.service.get('hr/appraisalchecklist.php?type=update_HR_status&rise='+this.selectedResult['rise'] +'&status='+status+'&id='+this.selectedResult['id']).subscribe(response => {
        if (response['status'] == 'success') {
          alert('Saved Successfully');
          this.getapprisals_log()
          this.isView = false;
        
          } else {
          alert('Failed: An error occured, please try again!');
        }
      });
    }

    exportToExcel(): void {
      const formattedData = this.results.map((user, index) => ({
        'Sr.': index + 1,
        'Employee Name': `${user.firstname} ${user.lastname} - ${user.emp_id}`,
        'Appraisal type': user.apprisal_type,
        'Expected Raise (in %)': user.rise,
        'Expected Promotion': user.designation || 'NA',
        'Plant Head status': user.plant_head,
        'Department Head Status': user.dept_head
      }));
  
      const worksheet: XLSX.WorkSheet = XLSX.utils.json_to_sheet(formattedData);
      const workbook: XLSX.WorkBook = {
        Sheets: { 'data': worksheet },
        SheetNames: ['data']
      };
  
      XLSX.writeFile(workbook, 'AppraisalData.xlsx');
    }

  
    
  }
  