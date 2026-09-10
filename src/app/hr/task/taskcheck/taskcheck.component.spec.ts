import { ComponentFixture, TestBed } from '@angular/core/testing';

import { TaskcheckComponent } from './taskcheck.component';

describe('TaskcheckComponent', () => {
  let component: TaskcheckComponent;
  let fixture: ComponentFixture<TaskcheckComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ TaskcheckComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(TaskcheckComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
