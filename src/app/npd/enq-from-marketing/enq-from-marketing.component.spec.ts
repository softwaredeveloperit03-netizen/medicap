import { ComponentFixture, TestBed } from '@angular/core/testing';

import { EnqFromMarketingComponent } from './enq-from-marketing.component';

describe('EnqFromMarketingComponent', () => {
  let component: EnqFromMarketingComponent;
  let fixture: ComponentFixture<EnqFromMarketingComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ EnqFromMarketingComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(EnqFromMarketingComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
