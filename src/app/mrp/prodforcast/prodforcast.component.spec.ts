import { ComponentFixture, TestBed } from '@angular/core/testing';

import { ProdforcastComponent } from './prodforcast.component';

describe('ProdforcastComponent', () => {
  let component: ProdforcastComponent;
  let fixture: ComponentFixture<ProdforcastComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ ProdforcastComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(ProdforcastComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
