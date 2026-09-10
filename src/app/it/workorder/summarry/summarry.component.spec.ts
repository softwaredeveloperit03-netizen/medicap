import { ComponentFixture, TestBed } from '@angular/core/testing';

import { SummarryComponent } from './summarry.component';

describe('SummarryComponent', () => {
  let component: SummarryComponent;
  let fixture: ComponentFixture<SummarryComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ SummarryComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(SummarryComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
