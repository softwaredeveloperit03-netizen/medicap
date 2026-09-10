import { ComponentFixture, TestBed } from '@angular/core/testing';

import { BmrViewComponent } from './bmr-view.component';

describe('BmrViewComponent', () => {
  let component: BmrViewComponent;
  let fixture: ComponentFixture<BmrViewComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ BmrViewComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(BmrViewComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
