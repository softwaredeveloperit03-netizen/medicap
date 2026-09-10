import { ComponentFixture, TestBed } from '@angular/core/testing';

import { EditEmpFormComponent } from './edit-emp-form.component';

describe('EditEmpFormComponent', () => {
  let component: EditEmpFormComponent;
  let fixture: ComponentFixture<EditEmpFormComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ EditEmpFormComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(EditEmpFormComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
