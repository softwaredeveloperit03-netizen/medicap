import { ComponentFixture, TestBed } from '@angular/core/testing';

import { LogfinishedComponent } from './logfinished.component';

describe('LogfinishedComponent', () => {
  let component: LogfinishedComponent;
  let fixture: ComponentFixture<LogfinishedComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ LogfinishedComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(LogfinishedComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
