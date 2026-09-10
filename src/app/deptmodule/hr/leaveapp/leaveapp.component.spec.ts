import { ComponentFixture, TestBed } from '@angular/core/testing';

import { LeaveappComponent } from './leaveapp.component';

describe('LeaveappComponent', () => {
  let component: LeaveappComponent;
  let fixture: ComponentFixture<LeaveappComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ LeaveappComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(LeaveappComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
