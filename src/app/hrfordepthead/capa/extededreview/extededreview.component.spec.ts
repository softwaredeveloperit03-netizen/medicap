import { ComponentFixture, TestBed } from '@angular/core/testing';

import { ExtededreviewComponent } from './extededreview.component';

describe('ExtededreviewComponent', () => {
  let component: ExtededreviewComponent;
  let fixture: ComponentFixture<ExtededreviewComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ ExtededreviewComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(ExtededreviewComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
