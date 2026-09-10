import { ComponentFixture, TestBed } from '@angular/core/testing';

import { TaskdeptComponent } from './taskdept.component';

describe('TaskdeptComponent', () => {
  let component: TaskdeptComponent;
  let fixture: ComponentFixture<TaskdeptComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ TaskdeptComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(TaskdeptComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});









