import { ComponentFixture, TestBed } from '@angular/core/testing';

import { GeneratewologComponent } from './generatewolog.component';

describe('GeneratewologComponent', () => {
  let component: GeneratewologComponent;
  let fixture: ComponentFixture<GeneratewologComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ GeneratewologComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(GeneratewologComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
