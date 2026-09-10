import { ComponentFixture, TestBed } from '@angular/core/testing';

import { LogreviewComponent } from './logreview.component';

describe('LogreviewComponent', () => {
  let component: LogreviewComponent;
  let fixture: ComponentFixture<LogreviewComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ LogreviewComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(LogreviewComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
