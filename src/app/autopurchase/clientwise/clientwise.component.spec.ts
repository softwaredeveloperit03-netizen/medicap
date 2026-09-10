import { ComponentFixture, TestBed } from '@angular/core/testing';

import { ClientwiseComponent } from './clientwise.component';

describe('ClientwiseComponent', () => {
  let component: ClientwiseComponent;
  let fixture: ComponentFixture<ClientwiseComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ ClientwiseComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(ClientwiseComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
