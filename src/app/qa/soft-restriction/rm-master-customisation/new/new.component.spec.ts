import { ComponentFixture, TestBed } from '@angular/core/testing';
import { NewComponent } from './new.component';
import { RmMasterCustomisationService } from '../rm-master-customisation.service';
import { Router } from '@angular/router';
import { of } from 'rxjs';
import { FormsModule } from '@angular/forms';
import { RouterTestingModule } from '@angular/router/testing';

describe('NewComponent', () => {
  let component: NewComponent;
  let fixture: ComponentFixture<NewComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [NewComponent],
      imports: [FormsModule, RouterTestingModule],
      providers: [
        { provide: RmMasterCustomisationService, useValue: { saveRequest: () => of({ status: 'success' }) } }
      ]
    }).compileComponents();
    fixture = TestBed.createComponent(NewComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
