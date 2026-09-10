import { ComponentFixture, TestBed } from '@angular/core/testing';

import { PerformanceQComponent } from './performance-q.component';

describe('PerformanceQComponent', () => {
  let component: PerformanceQComponent;
  let fixture: ComponentFixture<PerformanceQComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ PerformanceQComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(PerformanceQComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
