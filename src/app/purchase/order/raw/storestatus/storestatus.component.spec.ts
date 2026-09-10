import { ComponentFixture, TestBed } from '@angular/core/testing';

import { StorestatusComponent } from './storestatus.component';

describe('StorestatusComponent', () => {
  let component: StorestatusComponent;
  let fixture: ComponentFixture<StorestatusComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ StorestatusComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(StorestatusComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
