import { ComponentFixture, TestBed } from '@angular/core/testing';

import { LogicalCleaningComponent } from './logical-cleaning.component';

describe('LogicalCleaningComponent', () => {
  let component: LogicalCleaningComponent;
  let fixture: ComponentFixture<LogicalCleaningComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ LogicalCleaningComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(LogicalCleaningComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
